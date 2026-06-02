<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum StatutBonDeCommande: string implements HasLabel, HasColor, HasIcon
{
    case EnAttente = 'en_attente';
    case Confirme = 'confirme';
    case EnCours = 'en_cours';
    case Realise = 'realise';
    case Annule = 'annule';

    public function getLabel(): ?string { return $this->label(); }

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

    public function getColor(): string|array|null { return $this->color(); }

    public function color(): string
    {
        return match($this) {
            self::EnAttente => 'warning',
            self::Confirme  => 'success',
            self::EnCours   => 'info',
            self::Realise   => 'emerald',
            self::Annule    => 'danger',
        };
    }

    public function getIcon(): ?string { return $this->icon(); }

    public function icon(): string
    {
        return match($this) {
            self::EnAttente => 'heroicon-o-clock',
            self::Confirme  => 'heroicon-o-check-circle',
            self::EnCours   => 'heroicon-o-play-circle',
            self::Realise   => 'heroicon-o-check-badge',
            self::Annule    => 'heroicon-o-x-circle',
        };
    }
}
