<?php

namespace App\Services\Crm;

use App\Enums\ProspectStatut;
use App\Models\Appel;
use App\Models\Opportunite;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Construit les données de reporting hebdomadaire (CDC WF5 / WF6).
 *
 * - Téléprospecteurs : activité phoning de la semaine + pipeline prospects.
 * - Commerciaux : RDV de la semaine + pipeline partenaires.
 */
class WeeklyReportService
{
    public function periode(): array
    {
        $debut = CarbonImmutable::now()->subWeek()->startOfWeek();
        $fin = $debut->endOfWeek();

        return [$debut, $fin];
    }

    /**
     * Rapport pour un téléprospecteur.
     */
    public function pourTeleprospecteur(User $user): array
    {
        [$debut, $fin] = $this->periode();

        $prospectsParStatut = Prospect::query()
            ->where('teleprospecteur_id', $user->id)
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $appelsSemaine = Appel::query()
            ->where(function ($q) use ($user) {
                $q->where('phoning_agent_id', $user->id)
                    ->orWhere('user_id', $user->id);
            })
            ->whereBetween('date_heure', [$debut, $fin])
            ->count();

        $rappelsAVenir = Prospect::query()
            ->where('teleprospecteur_id', $user->id)
            ->whereNotNull('rappel_planifie_at')
            ->where('rappel_planifie_at', '>=', CarbonImmutable::now())
            ->count();

        return [
            'user' => $user,
            'role' => 'teleprospecteur',
            'periode' => [$debut, $fin],
            'appels_semaine' => $appelsSemaine,
            'rappels_a_venir' => $rappelsAVenir,
            'prospects_par_statut' => $this->labelliserProspects($prospectsParStatut),
            'qf' => $prospectsParStatut[ProspectStatut::QF->value] ?? 0,
        ];
    }

    /**
     * Rapport pour un commercial.
     */
    public function pourCommercial(User $user): array
    {
        [$debut, $fin] = $this->periode();

        $rdvSemaine = RendezVous::query()
            ->where('commercial_id', $user->id)
            ->whereBetween('date_heure', [$debut, $fin])
            ->count();

        $rdvAVenir = RendezVous::query()
            ->where('commercial_id', $user->id)
            ->where('date_heure', '>=', CarbonImmutable::now())
            ->count();

        $partenairesActifs = Partenaire::query()
            ->whereHas('rendezVous', fn ($q) => $q->where('commercial_id', $user->id))
            ->actifs()
            ->count();

        $opportunitesActives = Opportunite::query()
            ->where('assigne_a', $user->id)
            ->actives()
            ->count();

        return [
            'user' => $user,
            'role' => 'commercial',
            'periode' => [$debut, $fin],
            'rdv_semaine' => $rdvSemaine,
            'rdv_a_venir' => $rdvAVenir,
            'partenaires_actifs' => $partenairesActifs,
            'opportunites_actives' => $opportunitesActives,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function destinataires(string $role): Collection
    {
        return User::query()
            ->where('actif', true)
            ->where('role_cache', $role)
            ->whereNotNull('email')
            ->get();
    }

    private function labelliserProspects(array $parStatut): array
    {
        return collect(ProspectStatut::cases())
            ->mapWithKeys(fn (ProspectStatut $s) => [
                $s->label() => $parStatut[$s->value] ?? 0,
            ])
            ->toArray();
    }
}
