<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum StatutDevis: string implements HasLabel, HasColor, HasIcon
{
    case Brouillon = 'brouillon';
    case Envoye = 'envoye';
    case Accepte = 'accepte';
    case Refuse = 'refuse';
    case Expire = 'expire';

    public function getLabel(): ?string { return $this->label(); }

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

    public function getColor(): string|array|null { return $this->color(); }

    public function color(): string
    {
        return match($this) {
            self::Brouillon => 'gray',
            self::Envoye    => 'warning',
            self::Accepte   => 'success',
            self::Refuse    => 'danger',
            self::Expire    => 'secondary',
        };
    }

    public function getIcon(): ?string { return $this->icon(); }

    public function icon(): string
    {
        return match($this) {
            self::Brouillon => 'heroicon-o-document-text',
            self::Envoye    => 'heroicon-o-paper-airplane',
            self::Accepte   => 'heroicon-o-check-badge',
            self::Refuse    => 'heroicon-o-x-circle',
            self::Expire    => 'heroicon-o-clock',
        };
    }
}
