<?php
// ============================================================
// app/Enums/StatutDevis.php
// ============================================================
namespace App\Enums;

enum StatutDevis: string
{
    case Brouillon = 'brouillon';
    case Envoye    = 'envoye';
    case Accepte   = 'accepte';
    case Refuse    = 'refuse';
    case Expire    = 'expire';

    public function label(): string
    {
        return match($this) {
            self::Brouillon => 'Brouillon',
            self::Envoye    => 'Envoyé',
            self::Accepte   => 'Accepté',
            self::Refuse    => 'Refusé',
            self::Expire    => 'Expiré',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Brouillon => 'gray',
            self::Envoye    => 'info',
            self::Accepte   => 'success',
            self::Refuse    => 'danger',
            self::Expire    => 'warning',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Brouillon => 'heroicon-o-pencil',
            self::Envoye    => 'heroicon-o-paper-airplane',
            self::Accepte   => 'heroicon-o-check-circle',
            self::Refuse    => 'heroicon-o-x-circle',
            self::Expire    => 'heroicon-o-clock',
        };
    }

    /** Statuts où le devis peut encore être relancé */
    public function estActif(): bool
    {
        return in_array($this, [self::Brouillon, self::Envoye]);
    }
}


// ============================================================
// app/Enums/StatutBonDeCommande.php
// ============================================================
namespace App\Enums;

enum StatutBonDeCommande: string
{
    case EnAttente = 'en_attente';
    case Confirme  = 'confirme';
    case EnCours   = 'en_cours';
    case Realise   = 'realise';
    case Annule    = 'annule';

    public function label(): string
    {
        return match($this) {
            self::EnAttente => 'En attente',
            self::Confirme  => 'Confirmé',
            self::EnCours   => 'En cours',
            self::Realise   => 'Réalisé',
            self::Annule    => 'Annulé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::EnAttente => 'warning',
            self::Confirme  => 'info',
            self::EnCours   => 'primary',
            self::Realise   => 'success',
            self::Annule    => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::EnAttente => 'heroicon-o-clock',
            self::Confirme  => 'heroicon-o-check',
            self::EnCours   => 'heroicon-o-wrench-screwdriver',
            self::Realise   => 'heroicon-o-check-circle',
            self::Annule    => 'heroicon-o-x-circle',
        };
    }

    public function estActif(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirme, self::EnCours]);
    }

    public function statutsSuivants(): array
    {
        return match($this) {
            self::EnAttente => [self::Confirme, self::Annule],
            self::Confirme  => [self::EnCours, self::Realise, self::Annule],
            self::EnCours   => [self::Realise, self::Annule],
            self::Realise   => [],
            self::Annule    => [],
        };
    }
}


// ============================================================
// app/Enums/StatutPaiement.php
// ============================================================
namespace App\Enums;

enum StatutPaiement: string
{
    case EnAttente = 'en_attente';
    case Partiel   = 'partiel';
    case Paye      = 'paye';
    case EnRetard  = 'en_retard';
    case Litigieux = 'litigieux';

    public function label(): string
    {
        return match($this) {
            self::EnAttente => 'En attente',
            self::Partiel   => 'Partiel',
            self::Paye      => 'Payé',
            self::EnRetard  => 'En retard',
            self::Litigieux => 'Litigieux',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::EnAttente => 'info',
            self::Partiel   => 'warning',
            self::Paye      => 'success',
            self::EnRetard  => 'danger',
            self::Litigieux => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::EnAttente => 'heroicon-o-clock',
            self::Partiel   => 'heroicon-o-banknotes',
            self::Paye      => 'heroicon-o-check-circle',
            self::EnRetard  => 'heroicon-o-exclamation-triangle',
            self::Litigieux => 'heroicon-o-shield-exclamation',
        };
    }

    public function estSolde(): bool
    {
        return $this === self::Paye;
    }

    public function necessiteRelance(): bool
    {
        return in_array($this, [self::EnRetard, self::Partiel]);
    }
}


// ============================================================
// app/Enums/ModePaiement.php
// ============================================================
namespace App\Enums;

enum ModePaiement: string
{
    case Virement = 'virement';
    case CB       = 'cb';
    case Cheque   = 'cheque';
    case Especes  = 'especes';

    public function label(): string
    {
        return match($this) {
            self::Virement => 'Virement bancaire',
            self::CB       => 'Carte bancaire',
            self::Cheque   => 'Chèque',
            self::Especes  => 'Espèces',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Virement => 'heroicon-o-building-library',
            self::CB       => 'heroicon-o-credit-card',
            self::Cheque   => 'heroicon-o-document-text',
            self::Especes  => 'heroicon-o-banknotes',
        };
    }
}
