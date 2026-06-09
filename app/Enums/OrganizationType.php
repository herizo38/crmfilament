<?php

namespace App\Enums;

enum OrganizationType: string
{
    case CSE = 'CSE';
    case Syndicat = 'Syndicat';
    case EntrepriseDirecte = 'Entreprise directe';
    case Association = 'Association';

    /**
     * Retourne un tableau pour les selects Filament
     */
    public static function pourSelect(): array
    {
        return [
            self::CSE->value => self::CSE->value,
            self::Syndicat->value => self::Syndicat->value,
            self::EntrepriseDirecte->value => self::EntrepriseDirecte->value,
            self::Association->value => self::Association->value,
        ];
    }

    /**
     * Retourne un label plus lisible
     */
    public function label(): string
    {
        return match($this) {
            self::CSE => 'CSE',
            self::Syndicat => 'Syndicat',
            self::EntrepriseDirecte => 'Entreprise directe',
            self::Association => 'Association',
        };
    }
}
