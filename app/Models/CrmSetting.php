<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmSetting extends Model
{
    protected $fillable = [
        'groupe',
        'cle',
        'valeur',
        'type',
        'label',
        'description',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    public function getCastedValueAttribute(): mixed
    {
        return match ($this->type) {
            'int', 'integer' => (int) $this->valeur,
            'bool', 'boolean' => filter_var($this->valeur, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($this->valeur ?? '[]', true),
            default => $this->valeur,
        };
    }
}
