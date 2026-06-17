<?php

namespace App\Filament\NsConseil\Pages;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Enums\ProspectStatut;
use App\Enums\StatutCampagneProspection;
use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use App\Models\Appel;
use App\Models\ArtisanProspection;
use App\Models\CampagnePhoning;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\ContactParticulier;
use App\Models\Prospect;
use App\Models\ScriptAppel;
use App\Models\StatutPhoning;
use App\Models\User;
use App\Services\Aopia\FicheGenerationService;
use App\Services\Crm\CrmProfileService;
use App\Services\Crm\CrmSettingsService;
use App\Support\CsePhoningWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PhoningWorkflow extends Page
{
    // protected static ?string $navigationIcon    = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationLabel = 'Campagne d\'appels';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 3;

    // protected static ?int    $navigationSort    = 2;
    protected static string $view = 'filament.ns-conseil.pages.phoning-workflow';

    public ?Model $currentContact = null;

    public string $contactType = '';

    public array $currentContactData = [];

    public string $commentaires = '';

    public string $statut_resultat = '';

    public string $rappel_date = '';

    public string $rappel_heure = '';

    // ── Champs Interlocuteur Standard (prospect) ──────────────────────
    public string $nom_interlocuteur_standard = '';

    public string $creneaux_permanence_cse = '';

    public string $email_general_standard = '';

    // ── Champs Interlocuteur CSE (prospect) ──────────────────────────
    public string $interlocuteur_nom = '';

    public string $interlocuteur_fonction = '';

    public string $interlocuteur_telephone = '';

    public string $interlocuteur_email = '';

    // ── Fiche Bleue (RDV confirmé) ───────────────────────────────────
    public string $lieu_rdv = '';

    public bool $invitation_agenda_envoyee = false;

    public bool $enregistrement_appel_joint = false;

    public string $enregistrement_raison = '';

    public string $besoins_exprimes = '';

    public string $objections_soulevees = '';

    public string $points_attention_rdv = '';

    // ── Fiche Verte (RDV à conclure) ─────────────────────────────────
    public string $presence_cse = '';

    public string $jour_dispo_appel = '';

    public string $activeScriptTab = 'accroche';

    public int $progress = 0;

    public int $total = 0;

    public int $completed = 0;

    public array $scripts = [];

    public ?int $supervisedUserId = null;

    public bool $isSupervisorMode = false;

    public array $contactQueue = [];

    public ?int $currentCampagneId = null;

    // ── Mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = Auth::user();

        $this->isSupervisorMode = app(CrmProfileService::class)
            ->userHasCapability($user, 'supervisor');

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
        $roles = app(CrmSettingsService::class)->get('roles.teleprospecteur_roles', ['teleprospecteur']);

        return User::where(function ($q) use ($roles) {
            $q->whereHas('roles', fn ($r) => $r->whereIn('name', $roles));
            foreach ($roles as $role) {
                $q->orWhere('role_cache', $role);
            }
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
        $userId = $this->supervisedUserId ?? Auth::id();
        $cacheKey = "phoning_queue_user_{$userId}";
        $ordered = Cache::get($cacheKey);

        if ($ordered) {
            $this->contactQueue = $this->prioriserFile($this->filterValidQueue($ordered));

            return;
        }

        $this->contactQueue = $this->buildDefaultQueue($userId);
        $this->contactQueue = $this->prioriserFile($this->contactQueue);
    }

    protected function filterValidQueue(array $queue): array
    {
        $retireCodes = StatutPhoning::query()
            ->where('model_type', 'prospect')
            ->where('retire_de_file', true)
            ->pluck('code')
            ->all();

        return collect($queue)->filter(function ($item) use ($retireCodes) {
            return match ($item['type']) {
                'prospect' => Prospect::where('id', $item['id'])
                    ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                    ->whereNull('deleted_at')
                    ->exists()
                    && ! Appel::query()
                        ->where('appelable_type', Prospect::class)
                        ->where('appelable_id', $item['id'])
                        ->whereIn('phoning_status', $retireCodes)
                        ->exists(),
                'partenaire' => ContactPartenaire::where('id', $item['id'])
                    ->whereNull('deleted_at')
                    ->exists(),
                'client' => Client::where('id', $item['id'])
                    ->whereNull('deleted_at')
                    ->where(fn ($q) => $q->whereNull('ne_plus_contacter')->orWhere('ne_plus_contacter', false))
                    ->exists(),
                default => true,
            };
        })->values()->toArray();
    }

    protected function buildDefaultQueue(int $userId): array
    {
        $queue = [];
        $seen = [];

        $query = CampagnePhoning::active()->forUser($userId);

        // Si une campagne spécifique est demandée, ne charger que celle-là
        if ($this->currentCampagneId) {
            $query->where('id', $this->currentCampagneId);
        }

        $campagnes = $query->get();

        foreach ($campagnes as $campagne) {
            foreach ($campagne->getContactsQueue() as $contact) {
                $key = $contact['type'].'_'.$contact['id'];
                if (! isset($seen[$key])) {
                    $seen[$key] = true;
                    $queue[] = $contact;
                }
            }
        }

        return $queue;
    }

    /**
     * RAPL-ELU et rappels en retard passent en tête de file (workflow v2).
     */
    protected function prioriserFile(array $queue): array
    {
        if (empty($queue)) {
            return $queue;
        }

        $prioritaires = [];
        $normaux = [];

        foreach ($queue as $item) {
            if (($item['type'] ?? '') !== 'prospect') {
                $normaux[] = $item;

                continue;
            }

            $prospect = Prospect::find($item['id']);
            if (! $prospect) {
                continue;
            }

            $estPrioritaire = $prospect->rappel_est_en_retard
                || ($prospect->rappel_planifie_at && $prospect->rappel_planifie_at->isToday());

            if ($estPrioritaire) {
                $prioritaires[] = $item;
            } else {
                $normaux[] = $item;
            }
        }

        return array_merge($prioritaires, $normaux);
    }

    // ── Prochain contact ──────────────────────────────────────────────
    public function loadNextContact(): void
    {
        if (empty($this->contactQueue)) {
            $this->currentContact = null;
            $this->currentContactData = [];
            $this->scripts = [];
            $this->total = 0;
            $this->progress = 0;

            Notification::make()
                ->title('🎉 File vide !')
                ->body('Aucun contact à appeler pour le moment.')
                ->success()
                ->send();

            return;
        }

        $this->total = count($this->contactQueue);
        $this->progress = $this->total > 0
            ? round(($this->completed / $this->total) * 100)
            : 0;

        $next = $this->contactQueue[0];
        $model = $this->resolveModel($next['type'], $next['id']);

        if (! $model) {
            array_shift($this->contactQueue);
            $this->loadNextContact();

            return;
        }

        $this->currentContact = $model;
        $this->contactType = $next['type'];
        $this->currentCampagneId = $next['campagne_id'] ?? null;
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
            $this->creneaux_permanence_cse = $model->creneaux_permanence_cse ?? '';
            $this->email_general_standard = $model->email_general_standard ?? '';
            $this->interlocuteur_nom = $model->interlocuteur_nom ?? '';
            $this->interlocuteur_fonction = $model->interlocuteur_fonction ?? '';
            $this->interlocuteur_telephone = $model->interlocuteur_telephone ?? '';
            $this->interlocuteur_email = $model->interlocuteur_email ?? '';
        }

        $this->activeScriptTab = 'accroche';
    }

    protected function resolveModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'prospect' => Prospect::find($id),
            'artisan' => ArtisanProspection::find($id),
            'partenaire' => ContactPartenaire::find($id),
            'particulier' => ContactParticulier::find($id),
            'client' => Client::find($id),
            default => null,
        };
    }

    protected function buildContactData(Model $model, string $type): array
    {
        return match ($type) {
            'prospect' => [
                'nom' => $model->nom,
                'prenom' => null,
                'siret' => $model->siret,
                'type_pressenti' => $model->type_pressenti_label,
                'secteur_activite' => $model->secteur_activite,
                'nb_salaries' => $model->nb_salaries,
                'chiffre_affaires' => $model->chiffre_affaires
                    ? number_format($model->chiffre_affaires, 0, ',', ' ').' €'
                    : null,
                'telephone' => $model->telephone,
                'telephone_alt' => $model->telephone_alt,
                'email' => $model->email,
                'adresse' => $model->adresse,
                'ville' => $model->ville,
                'code_postal' => $model->code_postal,
                'departement' => $model->departement,
                'adresse_complete' => $model->adresse_complete,
                'interlocuteur_nom' => $model->interlocuteur_nom,
                'interlocuteur_fonction' => $model->interlocuteur_fonction,
                'interlocuteur_telephone' => $model->interlocuteur_telephone,
                'interlocuteur_email' => $model->interlocuteur_email,
                'interlocuteur' => $model->interlocuteur_complet,
                'statut' => $model->statut_label,
                'statut_color' => $model->statut_color,
                'statut_description' => $model->statut_description,
                'taux_engagement' => $model->taux_engagement,
                'priorite' => $model->type_pressenti
                    ? ucfirst(str_replace('_', ' ', $model->type_pressenti))
                    : 'Standard',
                'teleprospecteur' => $model->teleprospecteur
                    ? trim("{$model->teleprospecteur->prenom} {$model->teleprospecteur->nom}")
                    : null,
                'commercial' => $model->commercial
                    ? trim("{$model->commercial->prenom} {$model->commercial->nom}")
                    : null,
                'date_premier_contact' => $model->date_premier_contact?->format('d/m/Y'),
                'rappel_planifie_at' => $model->rappel_planifie_at?->format('d/m/Y à H:i'),
                'rappel_en_retard' => $model->rappel_est_en_retard,
                'jours_depuis_contact' => $model->jours_depuis_premier_contact,
                'notes' => $model->description,
                'motif_ko' => $model->motif_ko,
                'qf_valide' => $model->qf_valide,
                'id' => $model->id,
                'type' => 'prospect',
            ],
            'artisan' => [
                'nom' => $model->nom,
                'prenom' => null,
                'telephone' => $model->telephone,
                'telephone_alt' => null,
                'email' => null,
                'statut' => $model->statut_campagne->label(),
                'statut_color' => 'info',
                'priorite' => $model->priorite_segment->label(),
                'metier' => $model->corps_de_metier?->label(),
                'notes' => $model->notes,
                'id' => $model->id,
                'type' => 'artisan',
                'adresse_complete' => null,
                'interlocuteur' => null,
            ],
            'partenaire' => [
                'nom' => $model->nom,
                'prenom' => $model->prenom,
                'telephone' => $model->telephone_direct ?? $model->telephone_mobile ?? $model->telephone_perso,
                'telephone_alt' => null,
                'email' => $model->email ?? $model->email_perso,
                'statut' => $model->est_principal ? 'Principal' : 'Contact',
                'statut_color' => 'success',
                'priorite' => $model->niveau_influence_label ?? 'Standard',
                'notes' => $model->notes,
                'id' => $model->id,
                'type' => 'partenaire',
                'interlocuteur' => $model->fonction,
                'adresse_complete' => null,
            ],
            'particulier' => [
                'nom' => $model->nom,
                'prenom' => $model->prenom,
                'telephone' => $model->telephone,
                'telephone_alt' => null,
                'email' => $model->email,
                'statut' => $model->statut_occupant?->label() ?? 'Contact',
                'statut_color' => 'gray',
                'priorite' => $model->type_logement?->label() ?? 'Standard',
                'notes' => $model->adresse_complete,
                'id' => $model->id,
                'type' => 'particulier',
                'adresse_complete' => $model->adresse_complete ?? null,
                'interlocuteur' => null,
            ],
            'client' => [
                'nom' => $model->nom_tiers,
                'prenom' => $model->prenom,
                'telephone' => $model->telephone,
                'telephone_alt' => null,
                'email' => $model->email,
                'statut' => $model->etat ?? 'Client',
                'statut_color' => 'success',
                'priorite' => $model->type_tiers ?? 'Standard',
                'notes' => $model->entreprise,
                'adresse_complete' => $model->adresse_complete ?? null,
                'interlocuteur' => null,
                'id' => $model->id,
                'type' => 'client',
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
            'contact_nom' => $d['nom'] ?? '',
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
        if (! $this->currentContact) {
            return;
        }

        $codesValides = StatutPhoning::forModelType($this->contactType)
            ->pluck('code')
            ->implode(',');

        if (empty($codesValides)) {
            $codesValides = $this->contactType === 'client'
                ? 'std_nr,rp,ko'
                : 'nrp,fax,supp,maj,rdv,cse_ni,rapl_elu,rapl_std,bloc,bloc2,ncse_50,ncse_plus50,cse_zone,cse_hz';
        }

        $this->validate([
            'statut_resultat' => 'required|in:'.$codesValides,
            'commentaires' => $this->commentaireRequis() ? 'required|string|min:5|max:2000' : 'nullable|string|max:2000',
            'interlocuteur_email' => 'nullable|email',
            'email_general_standard' => 'nullable|email',
        ], [
            'commentaires.required' => $this->messageCommentaireObligatoire(),
        ]);

        match ($this->contactType) {
            'artisan' => $this->updateArtisan(),
            'partenaire' => $this->updatePartenaire(),
            'particulier' => $this->updateParticulier(),
            'prospect' => $this->updateProspect(),
            'client' => $this->updateClient(),
            default => null,
        };

        $this->enregistrerAppel();

        // Auto-génération des fiches Word liées au statut phoning
        if ($this->contactType === 'prospect' && $this->currentContact instanceof Prospect) {
            try {
                $ficheService = app(FicheGenerationService::class);
                $docs = $ficheService->genererAutoParStatut(
                    $this->statut_resultat,
                    $this->currentContact,
                    $this->currentContact->rendezVous()->latest('date_heure')->first()
                );
                if (! empty($docs)) {
                    $noms = collect($docs)->pluck('nom_fichier')->implode(', ');
                    Notification::make()
                        ->title('Fiches générées automatiquement')
                        ->body($noms)
                        ->info()
                        ->send();
                }
            } catch (\Throwable) {
                // Ne pas bloquer le workflow si la génération échoue
            }
        }

        Notification::make()
            ->title('Contact enregistré')
            ->body('Statut : '.$this->getResultLabel())
            ->success()
            ->send();

        array_shift($this->contactQueue);
        $this->completed++;

        $this->checkCampagneCompletion();

        $this->loadNextContact();
    }

    protected function checkCampagneCompletion(): void
    {
        if (! $this->currentCampagneId) {
            return;
        }

        $campagne = CampagnePhoning::find($this->currentCampagneId);
        if (! $campagne || $campagne->statut !== 'active') {
            return;
        }

        if ($campagne->estTerminee()) {
            $campagne->update(['statut' => 'terminee']);

            Notification::make()
                ->title('Campagne terminée !')
                ->body("Tous les contacts de « {$campagne->nom} » ont été traités.")
                ->success()
                ->duration(8000)
                ->send();
        }
    }

    protected function updateArtisan(): void
    {
        $artisan = $this->currentContact;
        $nouveauStatut = match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => StatutCampagneProspection::RP,
            'std_nr', 'cse_nr' => StatutCampagneProspection::NR,
            'ko' => StatutCampagneProspection::KO ?? StatutCampagneProspection::NR,
            default => StatutCampagneProspection::AC,
        };
        $artisan->changerStatut($nouveauStatut, $this->commentaires);
        $artisan->marquerContact();
        if ($this->statut_resultat === 'rp' && $this->rappel_date) {
            $artisan->ajouterNote("Rappel programmé le {$this->rappel_date}".($this->rappel_heure ? " {$this->rappel_heure}" : ''));
        }
    }

    protected function updatePartenaire(): void
    {
        $note = '[Appel du '.now()->format('d/m/Y H:i').'] '.$this->getResultLabel();
        if ($this->commentaires) {
            $note .= "\n{$this->commentaires}";
        }
        $this->currentContact->ajouterNote($note);
    }

    protected function updateParticulier(): void
    {
        $note = '[Appel du '.now()->format('d/m/Y H:i').'] '.$this->getResultLabel();
        if ($this->commentaires) {
            $note .= " - {$this->commentaires}";
        }
        $this->currentContact->update([
            'notes' => ($this->currentContact->notes ? $this->currentContact->notes."\n" : '').$note,
        ]);
    }

    protected function updateClient(): void
    {
        $note = '[Appel du '.now()->format('d/m/Y H:i').'] '.$this->getResultLabel();
        if ($this->commentaires) {
            $note .= " — {$this->commentaires}";
        }
        // Stocké dans extra_data car Client n'a pas de champ notes dédié
        $extra = $this->currentContact->extra_data ?? [];
        $extra['historique_appels'][] = $note;
        $this->currentContact->update(['extra_data' => $extra]);
    }

    protected function updateProspect(): void
    {
        $prospect = $this->currentContact;

        $statutMeta = StatutPhoning::where('model_type', 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();

        $nouveauStatut = $statutMeta?->pipeline_statut
            ? ProspectStatut::tryFrom($statutMeta->pipeline_statut)
            : null;

        if (! $nouveauStatut) {
            $nouveauStatut = ProspectStatut::AC;
        }

        $note = $this->getResultLabel();
        if ($this->commentaires) {
            $note .= " — {$this->commentaires}";
        }

        // Persist interlocutor & standard fields collected during the call
        $updateData = [];
        if ($this->nom_interlocuteur_standard !== '') {
            $updateData['nom_interlocuteur_standard'] = $this->nom_interlocuteur_standard;
        }
        if ($this->creneaux_permanence_cse !== '') {
            $updateData['creneaux_permanence_cse'] = $this->creneaux_permanence_cse;
        }
        if ($this->email_general_standard !== '') {
            $updateData['email_general_standard'] = $this->email_general_standard;
        }
        if ($this->interlocuteur_nom !== '') {
            $updateData['interlocuteur_nom'] = $this->interlocuteur_nom;
        }
        if ($this->interlocuteur_fonction !== '') {
            $updateData['interlocuteur_fonction'] = $this->interlocuteur_fonction;
        }
        if ($this->interlocuteur_telephone !== '') {
            $updateData['interlocuteur_telephone'] = $this->interlocuteur_telephone;
        }
        if ($this->interlocuteur_email !== '') {
            $updateData['interlocuteur_email'] = $this->interlocuteur_email;
        }
        if (! empty($updateData)) {
            $prospect->update($updateData);
        }

        if ($nouveauStatut === ProspectStatut::KO) {
            $prospect->marquerKO($note);
        } else {
            $prospect->changerStatut($nouveauStatut, $note);
        }
        $prospect->marquerContact();

        // Planifier le rappel selon paramètres back-office
        if ($this->rappel_date) {
            $this->appliquerRappelProspect($prospect);
        } elseif ($statutMeta?->delai_rappel_jours) {
            $prospect->programmerRappel(now()->addDays($statutMeta->delai_rappel_jours));
        } elseif ($statutMeta?->compte_comme_tentative) {
            $max = (int) app(CrmSettingsService::class)->get('prospection.max_standard_attempts', 3);
            $tentatives = $this->compterTentativesNonAbouties($prospect) + 1;
            if ($tentatives >= $max) {
                $stdNr = ProspectStatut::tryFrom('STD_NR') ?? ProspectStatut::STD_NR;
                $prospect->changerStatut($stdNr, "{$max} tentatives sans réponse");
                $jours = (int) app(CrmSettingsService::class)->get('prospection.std_nr_reminder_days', 2);
                $prospect->programmerRappel(now()->addDays($jours));
            }
        }
    }

    protected function appliquerRappelProspect(Prospect $prospect): void
    {
        try {
            $fmt = 'Y-m-d'.($this->rappel_heure ? ' H:i' : '');
            $val = $this->rappel_date.($this->rappel_heure ? ' '.$this->rappel_heure : '');
            $dt = \DateTime::createFromFormat($fmt, $val);
            if ($dt) {
                $prospect->programmerRappel($dt);
            }
        } catch (\Exception) {
        }
    }

    protected function commentaireRequis(): bool
    {
        if (blank($this->statut_resultat)) {
            return false;
        }

        $statut = StatutPhoning::where('model_type', $this->contactType ?: 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();

        return (bool) ($statut?->note_obligatoire);
    }

    protected function messageCommentaireObligatoire(): string
    {
        $statut = StatutPhoning::where('model_type', $this->contactType ?: 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();

        if ($statut?->message_note_obligatoire) {
            return 'Note obligatoire : '.$statut->message_note_obligatoire;
        }

        return 'Un commentaire est obligatoire pour ce statut.';
    }

    public function compterTentativesNonAbouties(?Model $contact = null): int
    {
        $contact = $contact ?? $this->currentContact;
        if (! $contact) {
            return 0;
        }

        $codes = StatutPhoning::where('model_type', 'prospect')
            ->where('compte_comme_tentative', true)
            ->pluck('code')
            ->toArray();

        if (empty($codes)) {
            $codes = ['nrp', 'fax', 'std_nr'];
        }

        return Appel::where('appelable_type', get_class($contact))
            ->where('appelable_id', $contact->id)
            ->whereIn('phoning_status', $codes)
            ->count();
    }

    // ── Fiches récap ──────────────────────────────────────────────────
    protected function determineFicheType(): ?string
    {
        $statut = StatutPhoning::where('model_type', $this->contactType ?: 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();

        return $statut?->fiche_type;
    }

    protected function buildFicheData(string $ficheType): array
    {
        $info = $this->currentContactData;
        $prospect = $this->currentContact;

        $base = [
            'raison_sociale' => $info['nom'] ?? null,
            'secteur_activite' => $info['secteur_activite'] ?? null,
            'effectif_total' => $info['nb_salaries'] ?? null,
            'adresse' => $info['adresse_complete'] ?? null,
            'interlocuteur_nom' => $this->interlocuteur_nom ?: ($info['interlocuteur_nom'] ?? null),
            'interlocuteur_fonction' => $this->interlocuteur_fonction ?: ($info['interlocuteur_fonction'] ?? null),
            'interlocuteur_telephone' => $this->interlocuteur_telephone ?: ($info['interlocuteur_telephone'] ?? null),
            'interlocuteur_email' => $this->interlocuteur_email ?: ($info['interlocuteur_email'] ?? null),
            'teleprospecteur_id' => Auth::id(),
            'commercial_id' => $prospect?->commercial_id ?? null,
            'date_appel' => now()->format('d/m/Y'),
        ];

        return match ($ficheType) {
            'bleue' => array_merge($base, [
                'date_rdv' => $this->rappel_date ?: null,
                'heure_rdv' => $this->rappel_heure ?: null,
                'lieu_rdv' => $this->lieu_rdv ?: null,
                'invitation_agenda_envoyee' => $this->invitation_agenda_envoyee,
                'enregistrement_appel_joint' => $this->enregistrement_appel_joint,
                'enregistrement_raison' => $this->enregistrement_raison ?: null,
                'besoins_exprimes' => $this->besoins_exprimes ?: null,
                'objections_soulevees' => $this->objections_soulevees ?: null,
                'points_attention_rdv' => $this->points_attention_rdv ?: null,
                'notes_interlocuteur' => $this->commentaires ?: null,
            ]),
            'jaune' => array_merge($base, [
                'commentaires' => $this->commentaires ?: null,
                'date_rappel' => $this->rappel_date ?: now()->addDays(7)->format('Y-m-d'),
                'heure_rappel' => $this->rappel_heure ?: null,
            ]),
            'verte' => array_merge($base, [
                'presence_cse' => $this->presence_cse ?: null,
                'jour_dispo_appel' => $this->jour_dispo_appel ?: null,
                'commentaires' => $this->commentaires ?: null,
                'date_rdv_a_prendre' => $this->rappel_date ?: null,
                'heure_rdv_a_prendre' => $this->rappel_heure ?: null,
            ]),
            default => [],
        };
    }

    // ── Journal d'appel ───────────────────────────────────────────────
    protected function enregistrerAppel(): void
    {
        if (! $this->currentContact) {
            return;
        }

        $eventResult = match ($this->statut_resultat) {
            'nrp', 'fax', 'std_nr', 'cse_nr' => EventResult::NonAbouti,
            'supp', 'cse_hz', 'ko' => EventResult::Annule,
            'rdv', 'rapl_elu', 'rapl_std', 'rp' => EventResult::Rappel,
            default => EventResult::Realise,
        };

        $ficheType = $this->determineFicheType();

        Appel::create([
            'appelable_type' => get_class($this->currentContact),
            'appelable_id' => $this->currentContact->id,
            'user_id' => Auth::id(),
            'type' => EventType::Appel,
            'date_heure' => now(),
            'resultat' => $eventResult,
            'commentaire' => $this->commentaires ?: null,
            'phoning_status' => $this->statut_resultat,
            'phoning_result' => $this->getResultLabel(),
            'phoning_notes' => $this->commentaires ?: null,
            'phoning_completed_at' => now(),
            'phoning_agent_id' => Auth::id(),
            'campagne_id' => $this->currentCampagneId,
            'fiche_type' => $ficheType,
            'fiche_data' => $ficheType ? $this->buildFicheData($ficheType) : null,
        ]);
    }

    public function getCallHistory(): array
    {
        if (! $this->currentContact) {
            return [];
        }

        return Appel::where('appelable_type', get_class($this->currentContact))
            ->where('appelable_id', $this->currentContact->id)
            ->with('user')
            ->orderBy('date_heure', 'desc')
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'date' => $a->date_heure->format('d/m/Y H:i'),
                'agent' => $a->user ? trim("{$a->user->prenom} {$a->user->nom}") : 'Système',
                'statut' => $a->phoning_status ?? $a->resultat?->value,
                'statut_label' => $a->phoning_result ?? $a->resultat?->label() ?? '—',
                'notes' => $a->phoning_notes ?? $a->commentaire,
                'campagne' => $a->campagne?->nom,
            ])
            ->toArray();
    }

    // ── Passer ────────────────────────────────────────────────────────
    public function skipCall(): void
    {
        if (empty($this->contactQueue)) {
            return;
        }
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
            ->map(fn ($u) => [
                'id' => $u->id,
                'nom_complet' => trim("{$u->prenom} {$u->nom}"),
                'initiales' => $u->initiales,
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
            ->map(fn ($s) => [
                'value' => $s->code,
                'label' => $s->label,
                'sub' => $s->description,
                'action' => $s->action_immediate,
                'couleur' => $s->couleur,
                'bar' => $s->couleur_css,
                'icon' => $s->icone,
                'note_obligatoire' => $s->note_obligatoire,
                'prioritaire' => $s->prioritaire,
                'fiche_type' => $s->fiche_type,
                'groupe' => $s->groupe,
                'groupe_label' => $s->groupe_label,
            ])
            ->toArray();
    }

    /**
     * Statuts prospect groupés par cas (workflow CSE v2).
     *
     * @return array<string, array{label: string, statuts: list<array>}>
     */
    public function getStatutsPhoningGroupes(): array
    {
        if (($this->contactType ?: 'prospect') !== 'prospect') {
            return ['default' => ['label' => 'Résultats', 'statuts' => $this->getStatutsPhoning()]];
        }

        return CsePhoningWorkflow::statutsGroupesPourProspect();
    }

    public function getTentativesAppel(): int
    {
        return $this->compterTentativesNonAbouties();
    }

    public function selectCampagne(int $campagneId): void
    {
        $this->currentCampagneId = $campagneId;
        $this->completed = 0;
        $this->loadQueue();
        $this->loadNextContact();

        $campagne = CampagnePhoning::find($campagneId);
        Notification::make()
            ->title('Campagne sélectionnée')
            ->body($campagne?->nom ?? 'Campagne #'.$campagneId)
            ->success()
            ->send();
    }

    public function clearCampagne(): void
    {
        $this->currentCampagneId = null;
        $this->completed = 0;
        $this->loadQueue();
        $this->loadNextContact();

        Notification::make()
            ->title('Toutes les campagnes')
            ->body('File rechargée avec toutes les campagnes actives.')
            ->info()
            ->send();
    }

    public function getCampagneInfo(): ?array
    {
        if (! $this->currentCampagneId) {
            return null;
        }

        $campagne = CampagnePhoning::find($this->currentCampagneId);
        if (! $campagne) {
            return null;
        }

        $stats = $campagne->getStats();

        return [
            'id' => $campagne->id,
            'nom' => $campagne->nom,
            'statut' => $campagne->statut,
            'statut_label' => $campagne->statut_label,
            'type_entite' => $campagne->type_entite_label,
            'total_contacts' => $stats['total_contacts'],
            'contacts_traites' => $stats['contacts_traites'],
            'progression' => $stats['progression'],
            'total_appels' => $stats['total_appels'],
        ];
    }

    public function getCampagnesDisponibles(): array
    {
        $userId = $this->supervisedUserId ?? Auth::id();

        return CampagnePhoning::active()
            ->forUser($userId)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nom' => $c->nom,
                'type_entite' => $c->type_entite_label,
                'contacts' => $c->countContacts(),
            ])
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('choisir_campagne')
                ->label('Choisir une campagne')
                ->icon('heroicon-o-megaphone')
                ->color('info')
                ->form([
                    Select::make('campagne_id')
                        ->label('Campagne')
                        ->options(function () {
                            $userId = $this->supervisedUserId ?? Auth::id();

                            return CampagnePhoning::active()
                                ->forUser($userId)
                                ->get()
                                ->mapWithKeys(fn ($c) => [$c->id => "{$c->nom} ({$c->countContacts()} contacts)"]);
                        })
                        ->required()
                        ->searchable(),
                ])
                ->action(fn (array $data) => $this->selectCampagne((int) $data['campagne_id'])),

            Action::make('toutes_campagnes')
                ->label('Toutes les campagnes')
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->visible(fn () => $this->currentCampagneId !== null)
                ->action(fn () => $this->clearCampagne()),

            Action::make('voir_campagne')
                ->label('Stats campagne')
                ->icon('heroicon-o-chart-bar')
                ->color('success')
                ->visible(fn () => $this->currentCampagneId !== null)
                ->url(fn () => CampagnePhoningResource::getUrl('view', ['record' => $this->currentCampagneId]))
                ->openUrlInNewTab(),

            Action::make('workflow_cse')
                ->label('Workflow CSE v2')
                ->icon('heroicon-o-map')
                ->url(fn () => WorkflowProspectionCse::getUrl())
                ->openUrlInNewTab(),

            Action::make('statuts_cse')
                ->label('Statuts CSE v2')
                ->icon('heroicon-o-tag')
                ->url(fn () => StatutsAppelsCse::getUrl())
                ->openUrlInNewTab(),

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
                ->url(fn () => route('filament.ns-conseil.pages.phoning-back-office')),
        ];
    }
}
