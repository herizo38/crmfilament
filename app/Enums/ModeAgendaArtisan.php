<?php
namespace App\Enums;

enum ModeAgendaArtisan: string
{
    case ModeA = 'mode_a';
    case ModeB = 'mode_b';

    public function label(): string
    {
        return match($this) {
            self::ModeA => 'Mode A — Plages structurées',
            self::ModeB => 'Mode B — Rappel à la demande (dégradé)',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ModeA => 'success',
            self::ModeB => 'warning',
        };
    }

    public function estModeDEgrade(): bool
    {
        return $this === self::ModeB;
    }
}
