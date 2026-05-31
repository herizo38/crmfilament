<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\ArtisanProspection;
use App\Models\ContactPartenaire;
use App\Models\ContactParticulier;
use App\Enums\StatutCampagneProspection;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class PhoningWorkflow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationLabel = 'Campagne d\'appels';
    protected static ?string $navigationGroup = 'Activités';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.ns-conseil.pages.phoning-workflow';

    public ?Model $currentContact = null;
    public string $contactType = ''; // 'partenaire', 'particulier', 'artisan'
    public string $commentaires = '';
    public string $statut_resultat = '';
    public string $rappel_date = '';
    public string $rappel_heure = '';

    public int $progress = 0;
    public int $total = 0;
    public int $completed = 0;

    public function mount(): void
    {
        $this->loadNextContact();
    }

    public function loadNextContact(): void
    {
        // 1. ArtisanProspection avec statut actif ou à relancer
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
            ->map(fn($item) => (object) [
                'id' => $item->id,
                'type' => 'artisan',
                'nom' => $item->nom,
                'prenom' => null,
                'telephone' => $item->telephone,
                'email' => null,
                'statut_actuel' => $item->statut_campagne->label(),
                'priorite' => $item->priorite_segment->label(),
                'notes' => $item->notes,
                'model' => $item,
            ]);

        // 2. ContactPartenaire - CORRECTION : utiliser les colonnes réelles
        $partenaires = ContactPartenaire::query()
            ->where(function ($q) {
                // Vérifier si au moins un numéro de téléphone existe (contactable)
                $q->whereNotNull('telephone_direct')
                    ->orWhereNotNull('telephone_mobile')
                    ->orWhereNotNull('telephone_perso');
            })
            ->whereNull('deleted_at')
            ->get()
            ->map(fn($item) => (object) [
                'id' => $item->id,
                'type' => 'partenaire',
                'nom' => $item->nom,
                'prenom' => $item->prenom,
                'telephone' => $item->telephone_direct ?? $item->telephone_mobile ?? $item->telephone_perso,
                'email' => $item->email ?? $item->email_perso,
                'statut_actuel' => $item->est_principal ? 'Principal' : 'Contact',
                'priorite' => $item->niveau_influence_label ?? 'Standard',
                'notes' => $item->notes,
                'model' => $item,
            ]);

        // 3. ContactParticulier - filtrer ceux avec téléphone
        $particuliers = ContactParticulier::query()
            ->whereNotNull('telephone')
            ->get()
            ->map(fn($item) => (object) [
                'id' => $item->id,
                'type' => 'particulier',
                'nom' => $item->nom,
                'prenom' => $item->prenom,
                'telephone' => $item->telephone,
                'email' => $item->email,
                'statut_actuel' => $item->statut_occupant?->label() ?? 'Contact',
                'priorite' => $item->type_logement?->label() ?? 'Standard',
                'notes' => $item->adresse_complete,
                'model' => $item,
            ]);

        // Fusionner et trier par priorité
        $allContacts = $artisans->concat($partenaires)->concat($particuliers)
            ->sortByDesc(function ($contact) {
                $prioriteMap = [
                    'Haute' => 5,
                    'Très fort' => 5,
                    'Décisionnaire' => 5,
                    'Fort' => 4,
                    'Moyen' => 3,
                    'Faible' => 2,
                    'Standard' => 2,
                    'Basse' => 1,
                ];

                return $prioriteMap[$contact->priorite] ?? 1;
            });

        $nextContact = $allContacts->first();

        if ($nextContact) {
            $this->currentContact = $nextContact->model;
            $this->contactType = $nextContact->type;
        } else {
            $this->currentContact = null;
        }

        // Stats de progression (total des contacts contactables)
        $this->total = $artisans->count() + $partenaires->count() + $particuliers->count();

        // Pour le complété, vous pouvez stocker dans une table séparée ou utiliser un champ
        // Pour l'instant, on met 0 et on incrémente manuellement
        $this->progress = $this->total > 0 ? round(($this->completed / $this->total) * 100) : 0;

        if (!$nextContact) {
            Notification::make()
                ->title('🎉 Campagne terminée !')
                ->body('Plus de contacts à appeler pour le moment.')
                ->success()
                ->send();
        } else {
            $this->reset(['commentaires', 'statut_resultat', 'rappel_date', 'rappel_heure']);
        }
    }

    public function callNow(): \Illuminate\Http\RedirectResponse
    {
        if (!$this->currentContact) {
            Notification::make()
                ->title('Aucun contact')
                ->body('Impossible d\'appeler : aucun contact chargé')
                ->danger()
                ->send();
            return redirect()->back();
        }

        // Récupérer le numéro selon le type
        $phoneNumber = match ($this->contactType) {
            'artisan' => $this->currentContact->telephone,
            'partenaire' => $this->currentContact->telephone_principal,
            'particulier' => $this->currentContact->telephone,
            default => null,
        };

        if (!$phoneNumber) {
            Notification::make()
                ->title('Numéro manquant')
                ->body('Impossible d\'appeler : numéro non disponible')
                ->danger()
                ->send();
            return redirect()->back();
        }

        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Redirige vers Aircall Phone
        return redirect()->away("https://phone.aircall.io/call/{$phoneNumber}");
    }

    public function submitResult(): void
    {
        if (!$this->currentContact)
            return;

        $this->validate([
            'statut_resultat' => 'required|in:qualifie,non_joignable,rappel,a_relancer',
            'commentaires' => 'nullable|string|max:1000',
        ]);

        // Mettre à jour selon le type de contact
        match ($this->contactType) {
            'artisan' => $this->updateArtisan(),
            'partenaire' => $this->updatePartenaire(),
            'particulier' => $this->updateParticulier(),
        };

        Notification::make()
            ->title('Contact qualifié')
            ->body("Statut : " . $this->getResultLabel())
            ->success()
            ->send();

        $this->completed++;
        $this->loadNextContact();
    }

    protected function updateArtisan(): void
    {
        $artisan = $this->currentContact;

        $nouveauStatut = match ($this->statut_resultat) {
            'qualifie' => StatutCampagneProspection::RP, // Rendez-vous pris
            'non_joignable' => StatutCampagneProspection::NR, // Non répondu
            'rappel' => StatutCampagneProspection::OBJ, // Objection / à rappeler
            'a_relancer' => StatutCampagneProspection::AC, // Actif
            default => StatutCampagneProspection::AC,
        };

        $artisan->changerStatut($nouveauStatut, $this->commentaires);
        $artisan->marquerContact();

        if ($this->statut_resultat === 'rappel' && $this->rappel_date) {
            // Ajouter une note pour le rappel
            $rappelDateTime = $this->rappel_date;
            if ($this->rappel_heure) {
                $rappelDateTime .= ' ' . $this->rappel_heure;
            }
            $artisan->ajouterNote("Rappel programmé le {$rappelDateTime}");
        }
    }

    protected function updatePartenaire(): void
    {
        $partenaire = $this->currentContact;

        $note = "[Appel du " . now()->format('d/m/Y H:i') . "] ";
        $note .= match ($this->statut_resultat) {
            'qualifie' => "✅ Contact qualifié - Intéressé",
            'non_joignable' => "❌ Non joignable après appel",
            'rappel' => "🔄 Rappel à programmer" . ($this->rappel_date ? " le {$this->rappel_date}" : ""),
            'a_relancer' => "📞 À relancer ultérieurement",
            default => "Appel effectué",
        };

        if ($this->commentaires) {
            $note .= "\nCommentaires : {$this->commentaires}";
        }

        $partenaire->ajouterNote($note);
    }

    protected function updateParticulier(): void
    {
        $particulier = $this->currentContact;

        $note = "[Appel du " . now()->format('d/m/Y H:i') . "] ";
        $note .= match ($this->statut_resultat) {
            'qualifie' => "✅ Contact qualifié - Intéressé",
            'non_joignable' => "❌ Non joignable",
            'rappel' => "🔄 Rappel à programmer",
            'a_relancer' => "📞 À recontacter",
            default => "Appel effectué",
        };

        if ($this->commentaires) {
            $note .= " - {$this->commentaires}";
        }

        // Ajouter la note (vous pouvez ajouter un champ notes si nécessaire)
        $particulier->update([
            'notes' => $particulier->notes
                ? $particulier->notes . "\n" . $note
                : $note,
        ]);
    }

    public function skipCall(): void
    {
        if (!$this->currentContact)
            return;

        Notification::make()
            ->title('Contact ignoré')
            ->body('Passé au suivant, ce contact reste à contacter')
            ->warning()
            ->send();

        $this->loadNextContact();
    }

    protected function getResultLabel(): string
    {
        return match ($this->statut_resultat) {
            'qualifie' => '✅ Qualifié',
            'non_joignable' => '❌ Non joignable',
            'rappel' => '🔄 Rappel programmé',
            'a_relancer' => '📞 À relancer',
            default => $this->statut_resultat,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Rafraîchir')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->loadNextContact()),
        ];
    }

    public function getContactInfo(): array
    {
        if (!$this->currentContact)
            return [];

        return match ($this->contactType) {
            'artisan' => [
                'nom' => $this->currentContact->nom,
                'prenom' => null,
                'telephone' => $this->currentContact->telephone,
                'statut' => $this->currentContact->statut_campagne->label(),
                'priorite' => $this->currentContact->priorite_segment->label(),
                'metier' => $this->currentContact->corps_de_metier?->label(),
            ],
            'partenaire' => [
                'nom' => $this->currentContact->nom,
                'prenom' => $this->currentContact->prenom,
                'telephone' => $this->currentContact->telephone_principal,
                'statut' => $this->currentContact->est_principal ? 'Principal' : 'Contact',
                'priorite' => $this->currentContact->niveau_influence_label,
                'metier' => $this->currentContact->fonction,
            ],
            'particulier' => [
                'nom' => $this->currentContact->nom,
                'prenom' => $this->currentContact->prenom,
                'telephone' => $this->currentContact->telephone,
                'statut' => $this->currentContact->statut_occupant?->label() ?? 'Contact',
                'priorite' => $this->currentContact->type_logement?->label(),
                'metier' => null,
            ],
            default => [],
        };
    }
}