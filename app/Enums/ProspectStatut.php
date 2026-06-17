<?php

namespace App\Enums;

enum ProspectStatut: string
{
    case AC = 'AC';
    case STD_NR = 'STD_NR';
    case STD_Joint = 'STD_Joint';
    case CSE_NR = 'CSE_NR';
    case RP = 'RP';
    case RPC = 'RPC';
    case KO = 'KO';
    case QF = 'QF';

    public function label(): string
    {
        return match ($this) {
            self::AC => 'À contacter',
            self::STD_NR => 'Standard non répondu',
            self::STD_Joint => 'Standard joint',
            self::CSE_NR => 'CSE non répondu',
            self::RP => 'Rappel planifié',
            self::RPC => 'RDV à planifier / Contact qualifié',
            self::KO => 'Hors cible / Refus',
            self::QF => 'RDV qualifié',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AC => 'gray',
            self::STD_NR => 'warning',
            self::STD_Joint => 'info',
            self::CSE_NR => 'warning',
            self::RP => 'success',
            self::RPC => 'success',
            self::KO => 'danger',
            self::QF => 'primary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AC => 'heroicon-o-phone',
            self::STD_NR => 'heroicon-o-building-office',
            self::STD_Joint => 'heroicon-o-check-badge',
            self::CSE_NR => 'heroicon-o-user-group',
            self::RP => 'heroicon-o-clock',
            self::RPC => 'heroicon-o-calendar-days',
            self::KO => 'heroicon-o-x-circle',
            self::QF => 'heroicon-o-check-circle',
        };
    }

    /**
     * Matrice CDC AOPIA des transitions autorisées.
     * Le passage QF n'est pas ici : il passe par le service TL uniquement.
     *
     * @return list<self>
     */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::AC => [self::STD_NR, self::STD_Joint, self::KO],
            self::STD_NR => [self::STD_Joint, self::KO, self::AC],
            self::STD_Joint => [self::CSE_NR, self::RP, self::RPC, self::KO],
            self::CSE_NR => [self::RP, self::RPC, self::STD_Joint, self::KO],
            self::RP => [self::STD_Joint, self::CSE_NR, self::RPC, self::KO],
            self::RPC => [self::RP, self::KO],
            self::KO, self::QF => [],
        };
    }

    public function peutAllerVers(self $nouveauStatut): bool
    {
        return in_array($nouveauStatut, $this->transitionsAutorisees(), true);
    }

    public function estArchive(): bool
    {
        return $this === self::KO;
    }

    public function estQualifie(): bool
    {
        return $this === self::QF;
    }

    public function exigeRappel(): bool
    {
        return in_array($this, [self::STD_NR, self::CSE_NR, self::RP, self::RPC], true);
    }

    public static function pourSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
