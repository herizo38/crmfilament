<?php

namespace App\Enums;

enum OrganizationType: string
{
    case CSE = 'CSE';
    case Syndicat = 'Syndicat';
    case EntrepriseDirecte = 'Entreprise directe';
    case Association = 'Association';
    case PartenariatAnnule = 'Partenariat annulé';

    /**
     * Retourne un tableau pour les selects Filament
     */
    public static function pourSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
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
            self::PartenariatAnnule => 'Partenariat annulé',
        };
    }
}
