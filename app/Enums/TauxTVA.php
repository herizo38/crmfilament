<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum TauxTVA: string implements HasLabel, HasColor, HasIcon
{
    case Reduit        = '5.5';
    case Intermediaire = '10.0';
    case Normal        = '20.0';

    public function label(): string
    {
        return match($this) {
            self::Reduit        => '5,5 % (Réduit)',
            self::Intermediaire => '10 % (Intermédiaire)',
            self::Normal        => '20 % (Normal)',
        };
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function color(): string
    {
        return match($this) {
            self::Reduit        => 'success',
            self::Intermediaire => 'warning',
            self::Normal        => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return $this->icon();
    }

    public function icon(): string
    {
        return match($this) {
            self::Reduit        => 'heroicon-o-banknotes',
            self::Intermediaire => 'heroicon-o-currency-euro',
            self::Normal        => 'heroicon-o-receipt-percent',
        };
    }

    /**
     * Convertit la valeur string en float
     */
    public function valeur(): float
    {
        return (float) $this->value;
    }

    /**
     * Calcule le montant de TVA
     */
    public function calculerTVA(float $montantHT): float
    {
        return round($montantHT * ($this->valeur() / 100), 2);
    }

    /**
     * Calcule le montant TTC à partir du HT
     */
    public function calculerTTC(float $montantHT): float
    {
        return round($montantHT + $this->calculerTVA($montantHT), 2);
    }

    /**
     * Calcule le montant HT à partir du TTC
     */
    public function calculerHT(float $montantTTC): float
    {
        return round($montantTTC / (1 + ($this->valeur() / 100)), 2);
    }

    /**
     * Taux applicable aux travaux de rénovation énergétique
     */
    public static function pourRenovation(): self
    {
        return self::Reduit;
    }

    /**
     * Taux applicable aux travaux courants (plomberie, électricité…)
     */
    public static function pourTravauxCourants(): self
    {
        return self::Intermediaire;
    }

    /**
     * Retourne tous les taux pour un select
     */
    public static function pourSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * Retourne le taux par défaut (20% normal)
     */
    public static function parDefaut(): self
    {
        return self::Normal;
    }
}
