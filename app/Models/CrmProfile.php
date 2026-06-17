<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmProfile extends Model
{
    protected $fillable = [
        'role_name',
        'label',
        'description',
        'panels',
        'landing_path',
        'couleur',
        'icone',
        'ordre',
        'can_validate_qf',
        'can_import',
        'is_supervisor',
        'is_system',
        'actif',
    ];

    protected $casts = [
        'panels' => 'array',
        'can_validate_qf' => 'boolean',
        'can_import' => 'boolean',
        'is_supervisor' => 'boolean',
        'is_system' => 'boolean',
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    public function scopeActifs($query)
    {
        return $query->where('actif', true)->orderBy('ordre');
    }

    public function scopeForPanel($query, string $panel)
    {
        return $query->where('actif', true)
            ->whereJsonContains('panels', $panel);
    }
}
