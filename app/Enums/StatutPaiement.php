<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum StatutPaiement: string implements HasLabel, HasColor, HasIcon
{
    case EnAttente = 'en_attente';
    case Partiel = 'partiel';
    case Paye = 'paye';
    case EnRetard = 'en_retard';
    case Litigieux = 'litigieux';

    public function getLabel(): ?string { return $this->label(); }

    public function label(): string
    {
        return match($this) {
            self::EnAttente => 'En attente',
            self::Partiel   => 'Paiement partiel',
            self::Paye      => 'Payé',
            self::EnRetard  => 'En retard',
            self::Litigieux => 'Litigieux',
        };
    }

    public function getColor(): string|array|null { return $this->color(); }

    public function color(): string
    {
        return match($this) {
            self::EnAttente => 'warning',
            self::Partiel   => 'orange',
            self::Paye      => 'success',
            self::EnRetard  => 'danger',
            self::Litigieux => 'purple',
        };
    }

    public function getIcon(): ?string { return $this->icon(); }

    public function icon(): string
    {
        return match($this) {
            self::EnAttente => 'heroicon-o-clock',
            self::Partiel   => 'heroicon-o-banknotes',
            self::Paye      => 'heroicon-o-check-circle',
            self::EnRetard  => 'heroicon-o-exclamation-triangle',
            self::Litigieux => 'heroicon-o-scale',
        };
    }
}
