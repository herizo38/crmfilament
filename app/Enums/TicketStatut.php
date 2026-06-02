<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketStatut: string implements HasLabel, HasColor, HasIcon
{
    case AppelRecu                    = 'appel_recu';
    case EnQualification              = 'en_qualification';
    case FicheComplete                = 'fiche_complete';
    case FicheIncomplete              = 'fiche_incomplete';
    case RdvPlanifie                  = 'rdv_planifie';
    case RappelPromis                 = 'rappel_promis';
    case EnAttenteConfirmationArtisan = 'en_attente_confirmation_artisan';
    case ArtisanConfirme              = 'artisan_confirme';

    // NOUVEAUX STATUTS POUR LA CHAÎNE DOCUMENTAIRE
    case DevisEnAttente               = 'devis_en_attente';      // Devis émis, en attente réponse client
    case DevisAccepte                 = 'devis_accepte';         // Devis accepté, BC généré
    case InterventionRealisee         = 'intervention_realisee'; // Artisan a clôturé le BC
    case FactureEmise                 = 'facture_emise';         // Facture générée, en attente paiement
    case PaiementRecu                 = 'paiement_recu';         // Facture réglée (avant appel NPS J+1)

    case ClotureSatisfait             = 'cloture_satisfait';
    case SuiviQualiteRequis           = 'suivi_qualite_requis';
    case ReclamationOuverte           = 'reclamation_ouverte';
    case P8EnTraitement               = 'p8_en_traitement';
    case DossierCloture               = 'dossier_cloture';

    public function getLabel(): ?string { return $this->label(); }

    public function label(): string
    {
        return match($this) {
            self::AppelRecu                    => 'Appel reçu',
            self::EnQualification              => 'En qualification',
            self::FicheComplete                => 'Fiche complète',
            self::FicheIncomplete              => 'Fiche incomplète',
            self::RdvPlanifie                  => 'RDV planifié',
            self::RappelPromis                 => 'Rappel promis',
            self::EnAttenteConfirmationArtisan => 'En attente confirmation artisan',
            self::ArtisanConfirme              => 'Artisan confirmé',

            // NOUVEAUX LABELS
            self::DevisEnAttente               => 'Devis en attente',
            self::DevisAccepte                 => 'Devis accepté',
            self::InterventionRealisee         => 'Intervention réalisée',
            self::FactureEmise                 => 'Facture émise',
            self::PaiementRecu                 => 'Paiement reçu',

            self::ClotureSatisfait             => 'Clôture satisfait',
            self::SuiviQualiteRequis           => 'Suivi qualité requis',
            self::ReclamationOuverte           => 'Réclamation ouverte',
            self::P8EnTraitement               => 'P8 en traitement',
            self::DossierCloture               => 'Dossier clôturé',
        };
    }

    public function getColor(): string|array|null { return $this->color(); }

    public function color(): string
    {
        return match($this) {
            self::AppelRecu                    => 'info',
            self::EnQualification              => 'warning',
            self::FicheComplete                => 'success',
            self::FicheIncomplete              => 'danger',
            self::RdvPlanifie                  => 'primary',
            self::RappelPromis                 => 'orange',
            self::EnAttenteConfirmationArtisan => 'purple',
            self::ArtisanConfirme              => 'success',

            // NOUVELLES COULEURS
            self::DevisEnAttente               => 'warning',
            self::DevisAccepte                 => 'success',
            self::InterventionRealisee         => 'teal',
            self::FactureEmise                 => 'indigo',
            self::PaiementRecu                 => 'emerald',

            self::ClotureSatisfait             => 'emerald',
            self::SuiviQualiteRequis           => 'yellow',
            self::ReclamationOuverte           => 'red',
            self::P8EnTraitement               => 'amber',
            self::DossierCloture               => 'gray',
        };
    }

    public function getIcon(): ?string { return $this->icon(); }

    public function icon(): string
    {
        return match($this) {
            self::AppelRecu                    => 'heroicon-o-phone-arrow-down-left',
            self::EnQualification              => 'heroicon-o-magnifying-glass',
            self::FicheComplete                => 'heroicon-o-document-check',
            self::FicheIncomplete              => 'heroicon-o-document-minus',
            self::RdvPlanifie                  => 'heroicon-o-calendar',
            self::RappelPromis                 => 'heroicon-o-phone-arrow-up-right',
            self::EnAttenteConfirmationArtisan => 'heroicon-o-clock',
            self::ArtisanConfirme              => 'heroicon-o-check-badge',

            // NOUVELLES ICÔNES
            self::DevisEnAttente               => 'heroicon-o-document-text',
            self::DevisAccepte                 => 'heroicon-o-document-check',
            self::InterventionRealisee         => 'heroicon-o-wrench-screwdriver',
            self::FactureEmise                 => 'heroicon-o-receipt-percent',
            self::PaiementRecu                 => 'heroicon-o-credit-card',

            self::ClotureSatisfait             => 'heroicon-o-face-smile',
            self::SuiviQualiteRequis           => 'heroicon-o-clipboard-document-check',
            self::ReclamationOuverte           => 'heroicon-o-exclamation-triangle',
            self::P8EnTraitement               => 'heroicon-o-cog',
            self::DossierCloture               => 'heroicon-o-archive-box',
        };
    }

    public function estActif(): bool
    {
        return !in_array($this, [
            self::DossierCloture,
            self::ClotureSatisfait
        ]);
    }

    public function estBloquant(): bool
    {
        return in_array($this, [
            self::FicheIncomplete,
            self::ReclamationOuverte,
            self::SuiviQualiteRequis
        ]);
    }

    /**
     * Vérifie si le ticket est dans la phase financière (devis/facture)
     */
    public function estEnPhaseFinanciere(): bool
    {
        return in_array($this, [
            self::DevisEnAttente,
            self::DevisAccepte,
            self::FactureEmise,
            self::PaiementRecu,
        ]);
    }

    /**
     * Vérifie si le ticket est en attente d'action client
     */
    public function estEnAttenteClient(): bool
    {
        return in_array($this, [
            self::DevisEnAttente,
            self::RappelPromis,
        ]);
    }

    /**
     * Vérifie si le ticket est en attente d'action artisan
     */
    public function estEnAttenteArtisan(): bool
    {
        return in_array($this, [
            self::EnAttenteConfirmationArtisan,
            self::ArtisanConfirme,
        ]);
    }

    public function ordre(): int
    {
        return match($this) {
            self::AppelRecu                    => 1,
            self::EnQualification              => 2,
            self::FicheComplete                => 3,
            self::FicheIncomplete              => 4,
            self::RdvPlanifie                  => 5,
            self::RappelPromis                 => 6,
            self::EnAttenteConfirmationArtisan => 7,
            self::ArtisanConfirme              => 8,

            // ORDRE DES NOUVEAUX STATUTS
            self::DevisEnAttente               => 9,
            self::DevisAccepte                 => 10,
            self::InterventionRealisee         => 11,
            self::FactureEmise                 => 12,
            self::PaiementRecu                 => 13,

            self::ClotureSatisfait             => 14,
            self::SuiviQualiteRequis           => 15,
            self::ReclamationOuverte           => 16,
            self::P8EnTraitement               => 17,
            self::DossierCloture               => 18,
        };
    }

    /**
     * Retourne la progression en pourcentage (0-100)
     */
    public function progression(): int
    {
        $total = 18; // Nombre total de statuts
        $position = $this->ordre();
        return (int) round(($position / $total) * 100);
    }

    public function statutsSuivants(): array
    {
        return match($this) {
            self::AppelRecu                    => [self::EnQualification],
            self::EnQualification              => [self::FicheComplete, self::FicheIncomplete],
            self::FicheComplete                => [self::RdvPlanifie, self::RappelPromis],
            self::FicheIncomplete              => [self::EnQualification],
            self::RdvPlanifie                  => [self::EnAttenteConfirmationArtisan],
            self::RappelPromis                 => [self::EnQualification, self::DevisEnAttente],
            self::EnAttenteConfirmationArtisan => [self::ArtisanConfirme],
            self::ArtisanConfirme              => [self::DevisEnAttente, self::InterventionRealisee],

            // NOUVELLES TRANSITIONS
            self::DevisEnAttente               => [self::DevisAccepte, self::EnQualification], // Refus devis → requalification
            self::DevisAccepte                 => [self::InterventionRealisee],
            self::InterventionRealisee         => [self::FactureEmise, self::ClotureSatisfait, self::SuiviQualiteRequis],
            self::FactureEmise                 => [self::PaiementRecu],
            self::PaiementRecu                 => [self::ClotureSatisfait],

            self::ClotureSatisfait             => [self::DossierCloture],
            self::SuiviQualiteRequis           => [self::P8EnTraitement, self::DossierCloture],
            self::ReclamationOuverte           => [self::P8EnTraitement],
            self::P8EnTraitement               => [self::DossierCloture],
            self::DossierCloture               => [],
        };
    }

    /**
     * Retourne les statuts considérés comme "en cours de traitement"
     */
    public static function statutsActifs(): array
    {
        return array_filter(self::cases(), fn($statut) => $statut->estActif());
    }

    /**
     * Retourne les statuts terminaux
     */
    public static function statutsTerminaux(): array
    {
        return [
            self::DossierCloture,
            self::ClotureSatisfait,
        ];
    }

    /**
     * Retourne les statuts nécessitant une attention prioritaire
     */
    public static function statutsCritiques(): array
    {
        return [
            self::FicheIncomplete,
            self::ReclamationOuverte,
            self::SuiviQualiteRequis,
        ];
    }

    /**
     * Vérifie si le statut est valide pour une transition de devis
     */
    public static function peutEmissionDevis(): array
    {
        return [
            self::ArtisanConfirme,
            self::RdvPlanifie,
        ];
    }
}
