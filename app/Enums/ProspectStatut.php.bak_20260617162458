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
        return match($this) {
            self::AC => 'À contacter',
            self::STD_NR => 'Standard - Non référencé',
            self::STD_Joint => 'Standard - Joint',
            self::CSE_NR => 'CSE - Non référencé',
            self::RP => 'Réponse positive',
            self::RPC => 'Réponse positive CSE',
            self::KO => 'KO',
            self::QF => 'Qualifié',
        };
    }

    public function color(): string
    {
        return match($this) {
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
        return match($this) {
            self::AC => 'heroicon-o-phone',
            self::STD_NR => 'heroicon-o-building-office',
            self::STD_Joint => 'heroicon-o-check-badge',
            self::CSE_NR => 'heroicon-o-user-group',
            self::RP => 'heroicon-o-hand-thumb-up',
            self::RPC => 'heroicon-o-star',
            self::KO => 'heroicon-o-x-circle',
            self::QF => 'heroicon-o-check-circle',
        };
    }
}
