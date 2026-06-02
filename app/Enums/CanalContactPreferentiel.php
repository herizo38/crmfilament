<?php

namespace App\Enums;

enum CanalContactPreferentiel: string
{
    case Appel = 'appel';
    case SMS   = 'sms';
    case Email = 'email';

    public function label(): string
    {
        return match($this) {
            self::Appel => 'Appel téléphonique',
            self::SMS   => 'SMS',
            self::Email => 'Email',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Appel => 'heroicon-o-phone',
            self::SMS   => 'heroicon-o-chat-bubble-left',
            self::Email => 'heroicon-o-envelope',
        };
    }
}
