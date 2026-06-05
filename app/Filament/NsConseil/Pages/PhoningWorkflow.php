<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\ArtisanProspection;
use App\Models\ContactPartenaire;
use App\Models\ContactParticulier;
use App\Models\Prospect;
use App\Models\ScriptAppel;
use App\Enums\StatutCampagneProspection;
use App\Enums\ProspectStatut;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class PhoningWorkflow extends Page
{
    protected static ?string $navigationIcon    = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationLabel   = 'Campagne d\'appels';
    protected static ?string $navigationGroup   = 'Activités';
    protected static ?int    $navigationSort    = 2;
    protected static string  $view              = 'filament.ns-conseil.pages.phoning-workflow';

    // ── État du contact courant ──────────────────────────────────────
    public ?Model  $currentContact = null;
    public string  $contactType    = '';

    // ── Formulaire de compte rendu ───────────────────────────────────
    public string $commentaires    = '';
    public string $statut_resultat = '';
    public string $rappel_date     = '';
    public string $rappel_heure    = '';

    // ── Onglet actif du script ───────────────────────────────────────
    public string $activeScriptTab = 'accroche';

    // ── Progression ─────────────────────────────────────────────────
    public int $progress  = 0;
    public int $total     = 0;
    public int $completed = 0;

    // ── Scripts chargés (tableau onglet => ScriptAppel|null) ─────────
    public array $scripts = [];

    // ── Mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->loadNextContact();
    }

    // ── Chargement du prochain contact ───────────────────────────────
    public function loadNextContact(): void
    {
        // 1. ArtisanProspection
        $artisans = ArtisanProspection::query()
            ->whereIn('statut_campagne', [
                StatutCampagneProspection::AC,
                StatutCampagneProspection::NR,
                StatutCampagneProspection::OBJ,
            ])
            ->where(function ($q) {
                $q->whereNull('date_dernier_contact')
                  ->orWhere('date_dernier_contact', '<=', now()->subHours(72));
            })
            ->get()
            ->map(fn ($item) => (object) [
                'id'          => $item->id,
                'type'        => 'artisan',
                'nom'         => $item->nom,
                'prenom'      => null,
                'telephone'   => $item->telephone,
                'email'       => null,
                'statut_actuel' => $item->statut_campagne->label(),
                'priorite'    => $item->priorite_segment->label(),
                'notes'       => $item->notes,
                'model'       => $item,
            ]);

        // 2. ContactPartenaire
        $partenaires = ContactPartenaire::query()
            ->where(function ($q) {
                $q->whereNotNull('telephone_direct')
                  ->orWhereNotNull('telephone_mobile')
                  ->orWhereNotNull('telephone_perso');
            })
            ->whereNull('deleted_at')
            ->get()
            ->map(fn ($item) => (object) [
                'id'          => $item->id,
                'type'        => 'partenaire',
                'nom'         => $item->nom,
                'prenom'      => $item->prenom,
                'telephone'   => $item->telephone_direct ?? $item->telephone_mobile ?? $item->telephone_perso,
                'email'       => $item->email ?? $item->email_perso,
                'statut_actuel' => $item->est_principal ? 'Principal' : 'Contact',
                'priorite'    => $item->niveau_influence_label ?? 'Standard',
                'notes'       => $item->notes,
                'model'       => $item,
            ]);

        // 3. ContactParticulier
        $particuliers = ContactParticulier::query()
            ->whereNotNull('telephone')
            ->get()
            ->map(fn ($item) => (object) [
                'id'          => $item->id,
                'type'        => 'particulier',
                'nom'         => $item->nom,
                'prenom'      => $item->prenom,
                'telephone'   => $item->telephone,
                'email'       => $item->email,
                'statut_actuel' => $item->statut_occupant?->label() ?? 'Contact',
                'priorite'    => $item->type_logement?->label() ?? 'Standard',
                'notes'       => $item->adresse_complete,
                'model'       => $item,
            ]);

        // 4. Prospect AC
        $prospects = Prospect::query()
            ->where('statut', ProspectStatut::AC)
            ->where(function ($q) {
                $q->whereNull('date_premier_contact')
                  ->orWhere('date_premier_contact', '<=', now()->subHours(72));
            })
            ->whereNull('deleted_at')
            ->get()
            ->map(fn ($item) => (object) [
                'id'          => $item->id,
                'type'        => 'prospect',
                'nom'         => $item->nom,
                'prenom'      => null,
                'telephone'   => $item->telephone,
                'email'       => $item->email,
                'statut_actuel' => $item->statut_label,
                'priorite'    => $item->type_pressenti
                    ? ucfirst(str_replace('_', ' ', $item->type_pressenti))
                    : 'Standard',
                'notes'       => $item->description,
                'model'       => $item,
            ]);

        $prioriteMap = [
            'Haute' => 5, 'Très fort' => 5, 'Décisionnaire' => 5,
            'Fort'  => 4, 'Moyen'     => 3,
            'Faible' => 2, 'Standard' => 2, 'Basse' => 1,
        ];

        $allContacts = $artisans
            ->concat($partenaires)
            ->concat($particuliers)
            ->concat($prospects)
            ->sortByDesc(fn ($c) => $prioriteMap[$c->priorite] ?? 1);

        $nextContact = $allContacts->first();

        if ($nextContact) {
            $this->currentContact = $nextContact->model;
            $this->contactType    = $nextContact->type;
            $this->loadScripts();
        } else {
            $this->currentContact = null;
            $this->scripts        = [];
        }

        $this->total    = $allContacts->count();
        $this->progress = $this->total > 0 ? round(($this->completed / $this->total) * 100) : 0;

        if (! $nextContact) {
            Notification::make()
                ->title('🎉 Campagne terminée !')
                ->body('Plus de contacts à appeler pour le moment.')
                ->success()
                ->send();
        } else {
            $this->reset(['commentaires', 'statut_resultat', 'rappel_date', 'rappel_heure']);
            $this->activeScriptTab = 'accroche';
        }
    }

    // ── Chargement des scripts dynamiques ────────────────────────────
    protected function loadScripts(): void
    {
        $this->scripts = ScriptAppel::parOngletPourContact($this->contactType);
    }

    /**
     * Retourne le script interpolé pour l'onglet actif.
     * Accessible depuis le Blade via $this->getScriptCourant().
     */
    public function getScriptCourant(): ?ScriptAppel
    {
        return $this->scripts[$this->activeScriptTab] ?? null;
    }

    /**
     * Variables de remplacement pour les scripts.
     */
    public function getVariablesScript(): array
    {
        $info = $this->getContactInfo();

        return [
            'contact_nom'     => $info['nom']    ?? '',
            'contact_prenom'  => $info['prenom'] ?? '',
            'commercial_nom'  => Auth::user()?->name ?? '[VOTRE NOM]',
        ];
    }

    // ── Appel téléphonique ───────────────────────────────────────────
    /**
     * FIX : on retourne void et on redirige avec $this->redirect()
     * (Livewire v3 / Filament v3).
     */
    public function callNow(): void
    {
        if (! $this->currentContact) {
            Notification::make()
                ->title('Aucun contact')
                ->body('Impossible d\'appeler : aucun contact chargé')
                ->danger()
                ->send();
            return;
        }

        $info        = $this->getContactInfo();
        $phoneNumber = $info['telephone'] ?? null;

        if (! $phoneNumber) {
            Notification::make()
                ->title('Numéro manquant')
                ->body('Impossible d\'appeler : numéro non disponible')
                ->danger()
                ->send();
            return;
        }

        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Livewire v3 : $this->redirect() pour les redirections externes
        $this->redirect("https://phone.aircall.io/call/{$phoneNumber}");
    }

    // ── Enregistrement du résultat ───────────────────────────────────
    public function submitResult(): void
    {
        if (! $this->currentContact) return;

        $this->validate([
            'statut_resultat' => 'required|in:std_nr,std_joint,cse_nr,rp,rpc,ko',
            'commentaires'    => 'nullable|string|max:1000',
        ]);

        match ($this->contactType) {
            'artisan'     => $this->updateArtisan(),
            'partenaire'  => $this->updatePartenaire(),
            'particulier' => $this->updateParticulier(),
            'prospect'    => $this->updateProspect(),
            default       => null,
        };

        Notification::make()
            ->title('Contact qualifié')
            ->body('Statut : ' . $this->getResultLabel())
            ->success()
            ->send();

        $this->completed++;
        $this->loadNextContact();
    }

    // ── Mise à jour par type de contact ──────────────────────────────
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
            $rappelDateTime = $this->rappel_date . ($this->rappel_heure ? ' ' . $this->rappel_heure : '');
            $artisan->ajouterNote("Rappel programmé le {$rappelDateTime}");
        }
    }

    protected function updatePartenaire(): void
    {
        $note  = "[Appel du " . now()->format('d/m/Y H:i') . "] ";
        $note .= match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => "✅ Contact joint",
            'std_nr', 'cse_nr'        => "❌ Non joignable",
            'ko'                      => "🚫 Refus / KO",
            default                   => "Appel effectué",
        };

        if ($this->commentaires) $note .= "\n{$this->commentaires}";

        $this->currentContact->ajouterNote($note);
    }

    protected function updateParticulier(): void
    {
        $note  = "[Appel du " . now()->format('d/m/Y H:i') . "] ";
        $note .= match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => "✅ Joint",
            'std_nr', 'cse_nr'        => "❌ Non joignable",
            'ko'                      => "🚫 KO",
            default                   => "Appel",
        };

        if ($this->commentaires) $note .= " - {$this->commentaires}";

        $this->currentContact->update([
            'notes' => $this->currentContact->notes
                ? $this->currentContact->notes . "\n" . $note
                : $note,
        ]);
    }

    protected function updateProspect(): void
    {
        $prospect = $this->currentContact;

        $nouveauStatut = match ($this->statut_resultat) {
            'rp'       => ProspectStatut::RP,
            'rpc'      => ProspectStatut::RPC,
            'std_joint'=> ProspectStatut::STD_Joint,
            'std_nr'   => ProspectStatut::STD_NR,
            'cse_nr'   => ProspectStatut::CSE_NR,
            'ko'       => ProspectStatut::KO,
            default    => ProspectStatut::AC,
        };

        $note = match ($this->statut_resultat) {
            'rp'       => "✅ Réponse positive",
            'rpc'      => "✅ Réponse positive CSE",
            'std_joint'=> "📞 Standard joint",
            'std_nr'   => "❌ Standard non référencé",
            'cse_nr'   => "❌ CSE non référencé",
            'ko'       => "🚫 KO - Refus",
            default    => "Appel effectué",
        };

        if ($this->commentaires) $note .= " - {$this->commentaires}";

        if ($nouveauStatut === ProspectStatut::KO) {
            $prospect->marquerKO($note);
        } else {
            $prospect->changerStatut($nouveauStatut, $note);
        }

        $prospect->marquerContact();

        // Rappel programmé
        if (in_array($this->statut_resultat, ['rp', 'rpc']) && $this->rappel_date) {
            try {
                $rappelDateTime = \DateTime::createFromFormat(
                    'Y-m-d' . ($this->rappel_heure ? ' H:i' : ''),
                    $this->rappel_date . ($this->rappel_heure ? ' ' . $this->rappel_heure : '')
                );
                if ($rappelDateTime) {
                    $prospect->programmerRappel($rappelDateTime);
                }
            } catch (\Exception $e) {
                // silent
            }
        }
    }

    // ── Passer le contact ────────────────────────────────────────────
    public function skipCall(): void
    {
        if (! $this->currentContact) return;

        Notification::make()
            ->title('Contact ignoré')
            ->body('Passé au suivant.')
            ->warning()
            ->send();

        $this->loadNextContact();
    }

    // ── Helpers ──────────────────────────────────────────────────────
    protected function getResultLabel(): string
    {
        return match ($this->statut_resultat) {
            'std_nr'    => '❌ STD-NR',
            'std_joint' => '📞 STD-Joint',
            'cse_nr'    => '🟠 CSE-NR',
            'rp'        => '✅ RP – Rappel planifié',
            'rpc'       => '⭐ RPC – RDV à planifier',
            'ko'        => '🚫 KO',
            default     => $this->statut_resultat,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Rafraîchir')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->loadNextContact()),
        ];
    }

    public function getContactInfo(): array
    {
        if (! $this->currentContact) return [];

        return match ($this->contactType) {
            'artisan'    => [
                'nom'      => $this->currentContact->nom,
                'prenom'   => null,
                'telephone'=> $this->currentContact->telephone,
                'statut'   => $this->currentContact->statut_campagne->label(),
                'priorite' => $this->currentContact->priorite_segment->label(),
                'metier'   => $this->currentContact->corps_de_metier?->label(),
                'email'    => null,
            ],
            'partenaire' => [
                'nom'      => $this->currentContact->nom,
                'prenom'   => $this->currentContact->prenom,
                'telephone'=> $this->currentContact->telephone_principal,
                'statut'   => $this->currentContact->est_principal ? 'Principal' : 'Contact',
                'priorite' => $this->currentContact->niveau_influence_label,
                'metier'   => $this->currentContact->fonction,
                'email'    => $this->currentContact->email ?? $this->currentContact->email_perso,
            ],
            'particulier'=> [
                'nom'      => $this->currentContact->nom,
                'prenom'   => $this->currentContact->prenom,
                'telephone'=> $this->currentContact->telephone,
                'statut'   => $this->currentContact->statut_occupant?->label() ?? 'Contact',
                'priorite' => $this->currentContact->type_logement?->label(),
                'metier'   => null,
                'email'    => $this->currentContact->email,
            ],
            'prospect'   => [
                'nom'          => $this->currentContact->nom,
                'prenom'       => null,
                'telephone'    => $this->currentContact->telephone,
                'telephone_alt'=> $this->currentContact->telephone_alt,
                'statut'       => $this->currentContact->statut_label,
                'priorite'     => $this->currentContact->type_pressenti,
                'metier'       => $this->currentContact->secteur_activite,
                'email'        => $this->currentContact->email,
                'adresse'      => $this->currentContact->adresse_complete,
                'interlocuteur'=> $this->currentContact->interlocuteur_complet,
                'ville'        => $this->currentContact->ville,
                'code_postal'  => $this->currentContact->code_postal,
                'siret'        => $this->currentContact->siret,
            ],
            default => [],
        };
    }
}
