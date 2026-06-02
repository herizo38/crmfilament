<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ModePaiement: string implements HasLabel, HasColor, HasIcon
{
    case Virement = 'virement';
    case CB = 'cb';
    case Cheque = 'cheque';
    case Especes = 'especes';

    public function getLabel(): ?string { return $this->label(); }

    public function label(): string
    {
        return match($this) {
            self::Virement => 'Virement bancaire',
            self::CB       => 'Carte bancaire',
            self::Cheque   => 'Chèque',
            self::Especes  => 'Espèces',
        };
    }

    public function getColor(): string|array|null { return $this->color(); }

    public function color(): string
    {
        return match($this) {
            self::Virement => 'blue',
            self::CB       => 'green',
            self::Cheque   => 'purple',
            self::Especes  => 'orange',
        };
    }

    public function getIcon(): ?string { return $this->icon(); }

    public function icon(): string
    {
        return match($this) {
            self::Virement => 'heroicon-o-building-library',
            self::CB       => 'heroicon-o-credit-card',
            self::Cheque   => 'heroicon-o-banknotes',
            self::Especes  => 'heroicon-o-cash',
        };
    }
}
