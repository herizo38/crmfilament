<?php

namespace App\Services\Aopia;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AopiaProspectWorkflowService
{
    /**
     * Change le statut en respectant la matrice CDC AOPIA.
     */
    public function changerStatut(Prospect $prospect, ProspectStatut $nouveauStatut, User $acteur, ?string $note = null): Prospect
    {
        $statutActuel = $prospect->statut instanceof ProspectStatut
            ? $prospect->statut
            : ProspectStatut::from((string) $prospect->statut);

        if ($nouveauStatut === ProspectStatut::QF) {
            return $this->validerQf($prospect, $acteur);
        }

        if ($statutActuel->estArchive()) {
            throw new RuntimeException('Une fiche KO est archivée et ne peut plus changer de statut.');
        }

        if (! $statutActuel->peutAllerVers($nouveauStatut)) {
            throw new RuntimeException("Transition interdite : {$statutActuel->label()} → {$nouveauStatut->label()}.");
        }

        if ($nouveauStatut === ProspectStatut::KO && blank($note)) {
            throw new RuntimeException('Un motif est obligatoire pour passer une fiche en KO.');
        }

        if ($nouveauStatut === ProspectStatut::STD_NR) {
            $this->verifierTentativesStandard($prospect);
            $prospect->rappel_planifie_at = now()->addDays(config('aopia.prospection.std_nr_reminder_days', 2));
        }

        if ($nouveauStatut === ProspectStatut::RPC && ! $prospect->rappel_planifie_at) {
            $prospect->rappel_planifie_at = now()->addHours(config('aopia.prospection.rpc_delay_hours', 48));
        }

        $prospect->statut = $nouveauStatut;

        if ($note) {
            $prospect->description = trim(($prospect->description ? $prospect->description . "\n" : '') . '[' . now()->format('d/m/Y H:i') . '] ' . $note);
        }

        if ($nouveauStatut === ProspectStatut::KO) {
            $prospect->motif_ko = $note;
        }

        $prospect->save();

        return $prospect->refresh();
    }

    /**
     * Validation QF : Team Leader uniquement + 7 conditions obligatoires.
     */
    public function validerQf(Prospect $prospect, User $acteur): Prospect
    {
        if (! $this->estTeamLeader($acteur)) {
            throw new RuntimeException('Seul un Team Leader ou administrateur peut valider le statut QF.');
        }

        $manquants = $this->champsManquantsPourQf($prospect);

        if (! empty($manquants)) {
            throw new RuntimeException('Passage QF bloqué. Éléments manquants : ' . implode(', ', $manquants));
        }

        return DB::transaction(function () use ($prospect, $acteur) {
            $prospect->statut = ProspectStatut::QF;
            $prospect->qf_valide = true;
            $prospect->valide_par = $acteur->id;
            $prospect->qf_valide_at = now();
            $prospect->save();

            return $prospect->refresh();
        });
    }

    /**
     * Liste exacte des blocages QF définis par le CDC.
     *
     * @return list<string>
     */
    public function champsManquantsPourQf(Prospect $prospect): array
    {
        $rdv = $this->dernierRendezVous($prospect);
        $missing = [];

        if (! $rdv) {
            $missing[] = 'RDV créé';
        }

        if (blank($prospect->raison_sociale ?: $prospect->nom)) {
            $missing[] = 'Raison sociale';
        }

        if (blank($prospect->secteur_activite)) {
            $missing[] = "Secteur d'activité";
        }

        if (blank($prospect->nb_salaries)) {
            $missing[] = 'Effectif total';
        } elseif ((int) $prospect->nb_salaries < config('aopia.qf.minimum_employee_count', 12)) {
            $missing[] = 'Effectif insuffisant pour QF';
        }

        if (blank($prospect->telephone)) {
            $missing[] = 'Téléphone standard';
        }

        if (blank($prospect->departement) || blank($prospect->ville)) {
            $missing[] = 'Département / Ville';
        }

        if (blank($prospect->interlocuteur_nom)) {
            $missing[] = 'Prénom / Nom CSE';
        }

        if (blank($prospect->interlocuteur_email)) {
            $missing[] = 'Email CSE';
        }

        if (! $prospect->commercial_id) {
            $missing[] = 'Responsable de Secteur assigné';
        }

        if ($rdv) {
            if (blank($rdv->date_heure)) {
                $missing[] = 'Date et heure du RDV';
            }

            if (blank($rdv->lieu) && blank($rdv->adresse_lieu)) {
                $missing[] = 'Lieu du RDV';
            }

            if (! (bool) $rdv->email_confirmation_envoye) {
                $missing[] = 'Email confirmation CSE envoyé';
            }

            if (! (bool) $rdv->email_invitation_envoye) {
                $missing[] = 'Invitation agenda Responsable de Secteur envoyée';
            }

            if (blank($rdv->pdf_recap)) {
                $missing[] = 'Fiche récap PDF générée';
            }

            if (blank($rdv->enregistrement_audio)) {
                $missing[] = 'Enregistrement audio joint';
            }
        }

        return $missing;
    }

    public function dernierRendezVous(Prospect $prospect): ?RendezVous
    {
        return RendezVous::query()
            ->where('rdvable_type', Prospect::class)
            ->where('rdvable_id', $prospect->id)
            ->latest('date_heure')
            ->first();
    }

    private function estTeamLeader(User $user): bool
    {
        $roles = config('aopia.qf.team_leader_roles', ['team_leader', 'administrateur', 'super_admin']);

        return method_exists($user, 'hasAnyRole')
            ? $user->hasAnyRole($roles)
            : in_array($user->role_cache, $roles, true);
    }

    private function verifierTentativesStandard(Prospect $prospect): void
    {
        $max = (int) config('aopia.prospection.max_standard_attempts', 3);

        // Le projet possède déjà Appel, mais les colonnes peuvent évoluer.
        // On applique une vérification souple : si aucune table d'appels exploitable n'est liée,
        // on laisse la transition et on délègue le contrôle fin à l'action UI/CTI.
        if (! class_exists(\App\Models\Appel::class)) {
            return;
        }

        $count = \App\Models\Appel::query()
            ->where('appelable_type', Prospect::class)
            ->where('appelable_id', $prospect->id)
            ->count();

        if ($count > 0 && $count < $max) {
            throw new RuntimeException("STD-NR nécessite {$max} tentatives à des horaires différents. Tentatives actuelles : {$count}.");
        }
    }
}
