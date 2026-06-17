<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\ArtisanProspection;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\ContactParticulier;
use App\Models\Prospect;
use App\Models\ScriptAppel;
use App\Models\StatutPhoning;
use App\Models\User;
use App\Enums\EventResult;
use App\Enums\EventType;
use App\Enums\StatutCampagneProspection;
use App\Enums\ProspectStatut;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class PhoningWorkflow extends Page
{
    // protected static ?string $navigationIcon    = 'heroicon-o-phone-arrow-up-right';
    // protected static ?string $navigationLabel   = 'Campagne d\'appels';
    // protected static ?string $navigationGroup   = 'Activités';
    // protected static ?int    $navigationSort    = 2;
    protected static string  $view              = 'filament.ns-conseil.pages.phoning-workflow';

    public ?Model  $currentContact     = null;
    public string  $contactType        = '';
    public array   $currentContactData = [];

    public string $commentaires    = '';
    public string $statut_resultat = '';
    public string $rappel_date     = '';
    public string $rappel_heure    = '';

    // ── Champs Interlocuteur Standard (prospect) ──────────────────────
    public string $nom_interlocuteur_standard = '';
    public string $creneaux_permanence_cse    = '';
    public string $email_general_standard     = '';

    // ── Champs Interlocuteur CSE (prospect) ──────────────────────────
    public string $interlocuteur_nom       = '';
    public string $interlocuteur_fonction  = '';
    public string $interlocuteur_telephone = '';
    public string $interlocuteur_email     = '';

    // ── Fiche Bleue (RDV confirmé) ───────────────────────────────────
    public string $lieu_rdv                   = '';
    public bool   $invitation_agenda_envoyee  = false;
    public bool   $enregistrement_appel_joint = false;
    public string $enregistrement_raison      = '';
    public string $besoins_exprimes           = '';
    public string $objections_soulevees       = '';
    public string $points_attention_rdv       = '';

    // ── Fiche Verte (RDV à conclure) ─────────────────────────────────
    public string $presence_cse     = '';
    public string $jour_dispo_appel = '';

    public string $activeScriptTab = 'accroche';

    public int $progress  = 0;
    public int $total     = 0;
    public int $completed = 0;

    public array $scripts = [];

    public ?int  $supervisedUserId  = null;
    public bool  $isSupervisorMode  = false;
    public array $contactQueue      = [];
    public ?int  $currentCampagneId = null;

    // ── Mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = Auth::user();

        $this->isSupervisorMode = $user?->hasAnyRole([
            'super_admin',
            'administrateur',
            'responsable_plateau',
            'superviseur',
        ]) ?? false;

        $this->supervisedUserId = $user?->id;

        // Filtrer sur une campagne spécifique si passée en URL
        if ($campagneId = request()->query('campagne_id')) {
            $this->currentCampagneId = (int) $campagneId;
        }

        $this->loadQueue();
        $this->loadNextContact();
    }

    // ── Requête centrale téléprospecteurs ────────────────────────────
    // Double critère : rôle Spatie OU role_cache pour couvrir les deux cas
    protected function queryTeleprospecteurs()
    {
        return User::where(function ($q) {
            $q->whereHas('roles', fn($r) => $r->where('name', User::ROLE_TELEPROSPECTEUR))
                ->orWhere('role_cache', User::ROLE_TELEPROSPECTEUR);
        })
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom');
    }

    // ── Supervision ───────────────────────────────────────────────────
    public function selectSupervisedUser(int $userId): void
    {
        $this->supervisedUserId = $userId;
        $this->completed = 0;
        $this->loadQueue();
        $this->loadNextContact();
    }

    public function resetToSelf(): void
    {
        $this->supervisedUserId = Auth::id();
        $this->completed = 0;
        $this->loadQueue();
        $this->loadNextContact();
    }

    // ── File d'appels ─────────────────────────────────────────────────
    public function loadQueue(): void
    {
        $userId   = $this->supervisedUserId ?? Auth::id();
        $cacheKey = "phoning_queue_user_{$userId}";
        $ordered  = Cache::get($cacheKey);

        if ($ordered) {
            $this->contactQueue = $this->filterValidQueue($ordered);
            return;
        }

        $this->contactQueue = $this->buildDefaultQueue($userId);
    }

    protected function filterValidQueue(array $queue): array
    {
        return collect($queue)->filter(function ($item) {
            return match ($item['type']) {
                'prospect' => Prospect::where('id', $item['id'])
                    ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                    ->whereNull('deleted_at')
                    ->exists(),
                'partenaire' => ContactPartenaire::where('id', $item['id'])
                    ->whereNull('deleted_at')
                    ->exists(),
                'client' => Client::where('id', $item['id'])
                    ->whereNull('deleted_at')
                    ->where(fn($q) => $q->whereNull('ne_plus_contacter')->orWhere('ne_plus_contacter', false))
                    ->exists(),
                default => true,
            };
        })->values()->toArray();
    }

    protected function buildDefaultQueue(int $userId): array
    {
        $queue = [];
        $seen  = [];

        $query = CampagnePhoning::active()->forUser($userId);

        // Si une campagne spécifique est demandée, ne charger que celle-là
        if ($this->currentCampagneId) {
            $query->where('id', $this->currentCampagneId);
        }

        $campagnes = $query->get();

        foreach ($campagnes as $campagne) {
            foreach ($campagne->getContactsQueue() as $contact) {
                $key = $contact['type'] . '_' . $contact['id'];
                if (! isset($seen[$key])) {
                    $seen[$key] = true;
                    $queue[] = $contact;
                }
            }
        }

        return $queue;
    }

    // ── Prochain contact ──────────────────────────────────────────────
    public function loadNextContact(): void
    {
        if (empty($this->contactQueue)) {
            $this->currentContact     = null;
            $this->currentContactData = [];
            $this->scripts            = [];
            $this->total              = 0;
            $this->progress           = 0;

            Notification::make()
                ->title('🎉 File vide !')
                ->body('Aucun contact à appeler pour le moment.')
                ->success()
                ->send();
            return;
        }

        $this->total    = count($this->contactQueue);
        $this->progress = $this->total > 0
            ? round(($this->completed / $this->total) * 100)
            : 0;

        $next  = $this->contactQueue[0];
        $model = $this->resolveModel($next['type'], $next['id']);

        if (! $model) {
            array_shift($this->contactQueue);
            $this->loadNextContact();
            return;
        }

        $this->currentContact     = $model;
        $this->contactType        = $next['type'];
        $this->currentCampagneId  = $next['campagne_id'] ?? null;
        $this->currentContactData = $this->buildContactData($model, $next['type']);
        $this->loadScripts();

        $this->reset([
            'commentaires', 'statut_resultat', 'rappel_date', 'rappel_heure',
            'nom_interlocuteur_standard', 'creneaux_permanence_cse', 'email_general_standard',
            'interlocuteur_nom', 'interlocuteur_fonction', 'interlocuteur_telephone', 'interlocuteur_email',
            // Fiche Bleue
            'lieu_rdv', 'invitation_agenda_envoyee', 'enregistrement_appel_joint',
            'enregistrement_raison', 'besoins_exprimes', 'objections_soulevees', 'points_attention_rdv',
            // Fiche Verte
            'presence_cse', 'jour_dispo_appel',
        ]);

        // Pre-fill prospect interlocutor fields from the loaded model
        if ($next['type'] === 'prospect' && $model instanceof Prospect) {
            $this->nom_interlocuteur_standard = $model->nom_interlocuteur_standard ?? '';
            $this->creneaux_permanence_cse    = $model->creneaux_permanence_cse ?? '';
            $this->email_general_standard     = $model->email_general_standard ?? '';
            $this->interlocuteur_nom          = $model->interlocuteur_nom ?? '';
            $this->interlocuteur_fonction     = $model->interlocuteur_fonction ?? '';
            $this->interlocuteur_telephone    = $model->interlocuteur_telephone ?? '';
            $this->interlocuteur_email        = $model->interlocuteur_email ?? '';
        }

        $this->activeScriptTab = 'accroche';
    }

    protected function resolveModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'prospect'    => Prospect::find($id),
            'artisan'     => ArtisanProspection::find($id),
            'partenaire'  => ContactPartenaire::find($id),
            'particulier' => ContactParticulier::find($id),
            'client'      => Client::find($id),
            default       => null,
        };
    }

    protected function buildContactData(Model $model, string $type): array
    {
        return match ($type) {
            'prospect' => [
                'nom'              => $model->nom,
                'prenom'           => null,
                'siret'            => $model->siret,
                'type_pressenti'   => $model->type_pressenti_label,
                'secteur_activite' => $model->secteur_activite,
                'nb_salaries'      => $model->nb_salaries,
                'chiffre_affaires' => $model->chiffre_affaires
                    ? number_format($model->chiffre_affaires, 0, ',', ' ') . ' €'
                    : null,
                'telephone'        => $model->telephone,
                'telephone_alt'    => $model->telephone_alt,
                'email'            => $model->email,
                'adresse'          => $model->adresse,
                'ville'            => $model->ville,
                'code_postal'      => $model->code_postal,
                'departement'      => $model->departement,
                'adresse_complete' => $model->adresse_complete,
                'interlocuteur_nom'       => $model->interlocuteur_nom,
                'interlocuteur_fonction'  => $model->interlocuteur_fonction,
                'interlocuteur_telephone' => $model->interlocuteur_telephone,
                'interlocuteur_email'     => $model->interlocuteur_email,
                'interlocuteur'           => $model->interlocuteur_complet,
                'statut'                  => $model->statut_label,
                'statut_color'            => $model->statut_color,
                'statut_description'      => $model->statut_description,
                'taux_engagement'         => $model->taux_engagement,
                'priorite'                => $model->type_pressenti
                    ? ucfirst(str_replace('_', ' ', $model->type_pressenti))
                    : 'Standard',
                'teleprospecteur'  => $model->teleprospecteur
                    ? trim("{$model->teleprospecteur->prenom} {$model->teleprospecteur->nom}")
                    : null,
                'commercial'       => $model->commercial
                    ? trim("{$model->commercial->prenom} {$model->commercial->nom}")
                    : null,
                'date_premier_contact' => $model->date_premier_contact?->format('d/m/Y'),
                'rappel_planifie_at'   => $model->rappel_planifie_at?->format('d/m/Y à H:i'),
                'rappel_en_retard'     => $model->rappel_est_en_retard,
                'jours_depuis_contact' => $model->jours_depuis_premier_contact,
                'notes'                => $model->description,
                'motif_ko'             => $model->motif_ko,
                'qf_valide'            => $model->qf_valide,
                'id'                   => $model->id,
                'type'                 => 'prospect',
            ],
            'artisan' => [
                'nom'            => $model->nom,
                'prenom'         => null,
                'telephone'      => $model->telephone,
                'telephone_alt'  => null,
                'email'          => null,
                'statut'         => $model->statut_campagne->label(),
                'statut_color'   => 'info',
                'priorite'       => $model->priorite_segment->label(),
                'metier'         => $model->corps_de_metier?->label(),
                'notes'          => $model->notes,
                'id'             => $model->id,
                'type'           => 'artisan',
                'adresse_complete' => null,
                'interlocuteur'  => null,
            ],
            'partenaire' => [
                'nom'           => $model->nom,
                'prenom'        => $model->prenom,
                'telephone'     => $model->telephone_direct ?? $model->telephone_mobile ?? $model->telephone_perso,
                'telephone_alt' => null,
                'email'         => $model->email ?? $model->email_perso,
                'statut'        => $model->est_principal ? 'Principal' : 'Contact',
                'statut_color'  => 'success',
                'priorite'      => $model->niveau_influence_label ?? 'Standard',
                'notes'         => $model->notes,
                'id'            => $model->id,
                'type'          => 'partenaire',
                'interlocuteur' => $model->fonction,
                'adresse_complete' => null,
            ],
            'particulier' => [
                'nom'            => $model->nom,
                'prenom'         => $model->prenom,
                'telephone'      => $model->telephone,
                'telephone_alt'  => null,
                'email'          => $model->email,
                'statut'         => $model->statut_occupant?->label() ?? 'Contact',
                'statut_color'   => 'gray',
                'priorite'       => $model->type_logement?->label() ?? 'Standard',
                'notes'          => $model->adresse_complete,
                'id'             => $model->id,
                'type'           => 'particulier',
                'adresse_complete' => $model->adresse_complete ?? null,
                'interlocuteur'  => null,
            ],
            'client' => [
                'nom'             => $model->nom_tiers,
                'prenom'          => $model->prenom,
                'telephone'       => $model->telephone,
                'telephone_alt'   => null,
                'email'           => $model->email,
                'statut'          => $model->etat ?? 'Client',
                'statut_color'    => 'success',
                'priorite'        => $model->type_tiers ?? 'Standard',
                'notes'           => $model->entreprise,
                'adresse_complete' => $model->adresse_complete ?? null,
                'interlocuteur'   => null,
                'id'              => $model->id,
                'type'            => 'client',
            ],
            default => [],
        };
    }

    protected function loadScripts(): void
    {
        $this->scripts = ScriptAppel::parOngletPourContact($this->contactType, $this->currentCampagneId);
    }

    public function getScriptCourant(): ?ScriptAppel
    {
        return $this->scripts[$this->activeScriptTab] ?? null;
    }

    public function getVariablesScript(): array
    {
        $d = $this->currentContactData;
        return [
            'contact_nom'    => $d['nom']    ?? '',
            'contact_prenom' => $d['prenom'] ?? '',
            'commercial_nom' => Auth::user()?->name ?? '[VOTRE NOM]',
        ];
    }

    // ── Appel ─────────────────────────────────────────────────────────
    public function callNow(): void
    {
        $phoneNumber = $this->currentContactData['telephone'] ?? null;
        if (! $phoneNumber) {
            Notification::make()->title('Numéro manquant')->danger()->send();
            return;
        }
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        $this->redirect("https://phone.aircall.io/call/{$phoneNumber}");
    }

    // ── Enregistrement ────────────────────────────────────────────────
    public function submitResult(): void
    {
        if (! $this->currentContact) return;

        $codesValides = StatutPhoning::forModelType($this->contactType)
            ->pluck('code')
            ->implode(',');

        if (empty($codesValides)) {
            $codesValides = $this->contactType === 'client'
                ? 'std_nr,rp,ko'
                : 'nrp,fax,supp,maj,rdv,cse_ni,rapl_elu,rapl_std,bloc,bloc2,ncse_50,ncse_plus50,cse_zone,cse_hz';
        }

        $this->validate([
            'statut_resultat'  => 'required|in:' . $codesValides,
            'commentaires'     => 'nullable|string|max:2000',
            'interlocuteur_email'       => 'nullable|email',
            'email_general_standard'    => 'nullable|email',
        ]);

        match ($this->contactType) {
            'artisan'     => $this->updateArtisan(),
            'partenaire'  => $this->updatePartenaire(),
            'particulier' => $this->updateParticulier(),
            'prospect'    => $this->updateProspect(),
            'client'      => $this->updateClient(),
            default       => null,
        };

        $this->enregistrerAppel();

        Notification::make()
            ->title('Contact enregistré')
            ->body('Statut : ' . $this->getResultLabel())
            ->success()
            ->send();

        array_shift($this->contactQueue);
        $this->completed++;
        $this->loadNextContact();
    }

    protected function updateArtisan(): void
    {
        $artisan = $this->currentContact;
        $nouveauStatut = match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => StatutCampagneProspection::RP,
            'std_nr', 'cse_nr'        => StatutCampagneProspection::NR,
            'ko'                      => StatutCampagneProspection::KO ?? StatutCampagneProspection::NR,
            default                   => StatutCampagneProspection::AC,
        };
        $artisan->changerStatut($nouveauStatut, $this->commentaires);
        $artisan->marquerContact();
        if ($this->statut_resultat === 'rp' && $this->rappel_date) {
            $artisan->ajouterNote("Rappel programmé le {$this->rappel_date}" . ($this->rappel_heure ? " {$this->rappel_heure}" : ''));
        }
    }

    protected function updatePartenaire(): void
    {
        $note = "[Appel du " . now()->format('d/m/Y H:i') . "] " . $this->getResultLabel();
        if ($this->commentaires) $note .= "\n{$this->commentaires}";
        $this->currentContact->ajouterNote($note);
    }

    protected function updateParticulier(): void
    {
        $note = "[Appel du " . now()->format('d/m/Y H:i') . "] " . $this->getResultLabel();
        if ($this->commentaires) $note .= " - {$this->commentaires}";
        $this->currentContact->update([
            'notes' => ($this->currentContact->notes ? $this->currentContact->notes . "\n" : '') . $note,
        ]);
    }

    protected function updateClient(): void
    {
        $note = "[Appel du " . now()->format('d/m/Y H:i') . "] " . $this->getResultLabel();
        if ($this->commentaires) $note .= " — {$this->commentaires}";
        // Stocké dans extra_data car Client n'a pas de champ notes dédié
        $extra = $this->currentContact->extra_data ?? [];
        $extra['historique_appels'][] = $note;
        $this->currentContact->update(['extra_data' => $extra]);
    }

    protected function updateProspect(): void
    {
        $prospect = $this->currentContact;

        // Mapping 14 statuts CSE → ProspectStatut
        $nouveauStatut = match ($this->statut_resultat) {
            // Cas 2 : Élu joint
            'rdv'         => ProspectStatut::RPC,
            'cse_ni'      => ProspectStatut::RP,
            'rapl_elu'    => ProspectStatut::RP,
            // Cas 3 : Blocage standard
            'rapl_std'    => ProspectStatut::STD_Joint,
            'bloc'        => ProspectStatut::STD_Joint,
            'bloc2'       => ProspectStatut::CSE_NR,
            // Cas 4 : Pas de CSE
            'ncse_50'     => ProspectStatut::CSE_NR,
            'ncse_plus50' => ProspectStatut::STD_Joint,
            // Cas particulier : CSE centralisé
            'cse_zone'    => ProspectStatut::STD_Joint,
            'cse_hz'      => ProspectStatut::KO,
            // Cas 1 : Appel non abouti
            'nrp'         => ProspectStatut::STD_NR,
            'fax'         => ProspectStatut::STD_NR,
            'maj'         => ProspectStatut::AC,
            'supp'        => ProspectStatut::KO,
            // Anciens codes (rétrocompatibilité)
            'rp'          => ProspectStatut::RP,
            'rpc'         => ProspectStatut::RPC,
            'std_joint'   => ProspectStatut::STD_Joint,
            'std_nr'      => ProspectStatut::STD_NR,
            'cse_nr'      => ProspectStatut::CSE_NR,
            'ko'          => ProspectStatut::KO,
            default       => ProspectStatut::AC,
        };

        $note = $this->getResultLabel();
        if ($this->commentaires) $note .= " — {$this->commentaires}";

        // Persist interlocutor & standard fields collected during the call
        $updateData = [];
        if ($this->nom_interlocuteur_standard !== '')
            $updateData['nom_interlocuteur_standard'] = $this->nom_interlocuteur_standard;
        if ($this->creneaux_permanence_cse !== '')
            $updateData['creneaux_permanence_cse'] = $this->creneaux_permanence_cse;
        if ($this->email_general_standard !== '')
            $updateData['email_general_standard'] = $this->email_general_standard;
        if ($this->interlocuteur_nom !== '')
            $updateData['interlocuteur_nom'] = $this->interlocuteur_nom;
        if ($this->interlocuteur_fonction !== '')
            $updateData['interlocuteur_fonction'] = $this->interlocuteur_fonction;
        if ($this->interlocuteur_telephone !== '')
            $updateData['interlocuteur_telephone'] = $this->interlocuteur_telephone;
        if ($this->interlocuteur_email !== '')
            $updateData['interlocuteur_email'] = $this->interlocuteur_email;
        if (! empty($updateData)) {
            $prospect->update($updateData);
        }

        if ($nouveauStatut === ProspectStatut::KO) {
            $prospect->marquerKO($note);
        } else {
            $prospect->changerStatut($nouveauStatut, $note);
        }
        $prospect->marquerContact();

        // Planifier le rappel pour les codes qui génèrent une fiche ou un créneau
        $codesRappel = ['rdv', 'rapl_elu', 'rapl_std', 'cse_ni', 'rp', 'rpc'];
        if (in_array($this->statut_resultat, $codesRappel) && $this->rappel_date) {
            try {
                $fmt = 'Y-m-d' . ($this->rappel_heure ? ' H:i' : '');
                $val = $this->rappel_date . ($this->rappel_heure ? ' ' . $this->rappel_heure : '');
                $dt  = \DateTime::createFromFormat($fmt, $val);
                if ($dt) $prospect->programmerRappel($dt);
            } catch (\Exception) {
            }
        }
    }

    // ── Fiches récap ──────────────────────────────────────────────────
    protected function determineFicheType(): ?string
    {
        return match ($this->statut_resultat) {
            'rdv'                                          => 'bleue',
            'cse_ni'                                       => 'jaune',
            'bloc2', 'ncse_50', 'ncse_plus50', 'cse_zone' => 'verte',
            default                                        => null,
        };
    }

    protected function buildFicheData(string $ficheType): array
    {
        $info = $this->currentContactData;
        $prospect = $this->currentContact;

        $base = [
            'raison_sociale'          => $info['nom'] ?? null,
            'secteur_activite'        => $info['secteur_activite'] ?? null,
            'effectif_total'          => $info['nb_salaries'] ?? null,
            'adresse'                 => $info['adresse_complete'] ?? null,
            'interlocuteur_nom'       => $this->interlocuteur_nom ?: ($info['interlocuteur_nom'] ?? null),
            'interlocuteur_fonction'  => $this->interlocuteur_fonction ?: ($info['interlocuteur_fonction'] ?? null),
            'interlocuteur_telephone' => $this->interlocuteur_telephone ?: ($info['interlocuteur_telephone'] ?? null),
            'interlocuteur_email'     => $this->interlocuteur_email ?: ($info['interlocuteur_email'] ?? null),
            'teleprospecteur_id'      => Auth::id(),
            'commercial_id'           => $prospect?->commercial_id ?? null,
            'date_appel'              => now()->format('d/m/Y'),
        ];

        return match ($ficheType) {
            'bleue' => array_merge($base, [
                'date_rdv'                   => $this->rappel_date ?: null,
                'heure_rdv'                  => $this->rappel_heure ?: null,
                'lieu_rdv'                   => $this->lieu_rdv ?: null,
                'invitation_agenda_envoyee'  => $this->invitation_agenda_envoyee,
                'enregistrement_appel_joint' => $this->enregistrement_appel_joint,
                'enregistrement_raison'      => $this->enregistrement_raison ?: null,
                'besoins_exprimes'           => $this->besoins_exprimes ?: null,
                'objections_soulevees'       => $this->objections_soulevees ?: null,
                'points_attention_rdv'       => $this->points_attention_rdv ?: null,
                'notes_interlocuteur'        => $this->commentaires ?: null,
            ]),
            'jaune' => array_merge($base, [
                'commentaires' => $this->commentaires ?: null,
                'date_rappel'  => $this->rappel_date ?: now()->addDays(7)->format('Y-m-d'),
                'heure_rappel' => $this->rappel_heure ?: null,
            ]),
            'verte' => array_merge($base, [
                'presence_cse'       => $this->presence_cse ?: null,
                'jour_dispo_appel'   => $this->jour_dispo_appel ?: null,
                'commentaires'       => $this->commentaires ?: null,
                'date_rdv_a_prendre' => $this->rappel_date ?: null,
                'heure_rdv_a_prendre'=> $this->rappel_heure ?: null,
            ]),
            default => [],
        };
    }

    // ── Journal d'appel ───────────────────────────────────────────────
    protected function enregistrerAppel(): void
    {
        if (! $this->currentContact) return;

        $eventResult = match ($this->statut_resultat) {
            'nrp', 'fax', 'std_nr', 'cse_nr'       => EventResult::NonAbouti,
            'supp', 'cse_hz', 'ko'                  => EventResult::Annule,
            'rdv', 'rapl_elu', 'rapl_std', 'rp'     => EventResult::Rappel,
            default                                  => EventResult::Realise,
        };

        $ficheType = $this->determineFicheType();

        Appel::create([
            'appelable_type'       => get_class($this->currentContact),
            'appelable_id'         => $this->currentContact->id,
            'user_id'              => Auth::id(),
            'type'                 => EventType::Appel,
            'date_heure'           => now(),
            'resultat'             => $eventResult,
            'commentaire'          => $this->commentaires ?: null,
            'phoning_status'       => $this->statut_resultat,
            'phoning_result'       => $this->getResultLabel(),
            'phoning_notes'        => $this->commentaires ?: null,
            'phoning_completed_at' => now(),
            'phoning_agent_id'     => Auth::id(),
            'campagne_id'          => $this->currentCampagneId,
            'fiche_type'           => $ficheType,
            'fiche_data'           => $ficheType ? $this->buildFicheData($ficheType) : null,
        ]);
    }

    public function getCallHistory(): array
    {
        if (! $this->currentContact) return [];

        return Appel::where('appelable_type', get_class($this->currentContact))
            ->where('appelable_id', $this->currentContact->id)
            ->with('user')
            ->orderBy('date_heure', 'desc')
            ->limit(15)
            ->get()
            ->map(fn($a) => [
                'date'       => $a->date_heure->format('d/m/Y H:i'),
                'agent'      => $a->user ? trim("{$a->user->prenom} {$a->user->nom}") : 'Système',
                'statut'     => $a->phoning_status ?? $a->resultat?->value,
                'statut_label' => $a->phoning_result ?? $a->resultat?->label() ?? '—',
                'notes'      => $a->phoning_notes ?? $a->commentaire,
                'campagne'   => $a->campagne?->nom,
            ])
            ->toArray();
    }

    // ── Passer ────────────────────────────────────────────────────────
    public function skipCall(): void
    {
        if (empty($this->contactQueue)) return;
        $first = array_shift($this->contactQueue);
        $this->contactQueue[] = $first;
        Notification::make()->title('Contact passé')->body('Repoussé en fin de file.')->warning()->send();
        $this->loadNextContact();
    }

    protected function getResultLabel(): string
    {
        $statut = StatutPhoning::where('model_type', $this->contactType)
            ->where('code', $this->statut_resultat)
            ->first();

        if ($statut) {
            return trim("{$statut->icone} {$statut->label}");
        }

        return $this->statut_resultat;
    }

    // ── Données pour la vue ───────────────────────────────────────────
    public function getTeleprospecteurs(): array
    {
        return $this->queryTeleprospecteurs()
            ->get()
            ->map(fn($u) => [
                'id'           => $u->id,
                'nom_complet'  => trim("{$u->prenom} {$u->nom}"),
                'initiales'    => $u->initiales,
                'nb_prospects' => Prospect::where('teleprospecteur_id', $u->id)
                    ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                    ->whereNull('deleted_at')
                    ->count(),
            ])
            ->toArray();
    }

    public function getContactInfo(): array
    {
        return $this->currentContactData;
    }

    public function getStatutsPhoning(): array
    {
        $type = $this->contactType ?: 'prospect';
        return StatutPhoning::forModelType($type)
            ->map(fn($s) => [
                'value'   => $s->code,
                'label'   => $s->label,
                'sub'     => $s->description,
                'couleur' => $s->couleur,
                'bar'     => $s->couleur_css,
                'icon'    => $s->icone,
            ])
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Rafraîchir')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadQueue();
                    $this->loadNextContact();
                }),

            Action::make('back_office')
                ->label('Prioriser la file')
                ->icon('heroicon-o-queue-list')
                ->color('warning')
                ->url(fn() => route('filament.ns-conseil.pages.phoning-back-office')),
        ];
    }
}
