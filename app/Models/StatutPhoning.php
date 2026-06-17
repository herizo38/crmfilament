<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatutPhoning extends Model
{
    protected $fillable = [
        'model_type',
        'code',
        'label',
        'description',
        'couleur',
        'icone',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    const MODEL_TYPES = [
        'prospect'    => 'Prospect',
        'partenaire'  => 'Partenaire',
        'opportunite' => 'Opportunité',
        'client'      => 'Client',
    ];

    const COULEURS = [
        'gray'   => 'Gris',
        'blue'   => 'Bleu',
        'orange' => 'Orange',
        'green'  => 'Vert',
        'teal'   => 'Turquoise',
        'mint'   => 'Menthe',
        'red'    => 'Rouge',
        'yellow' => 'Jaune',
        'purple' => 'Violet',
        'pink'   => 'Rose',
    ];

    public static function forModelType(string $modelType): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('model_type', $modelType)
            ->where('actif', true)
            ->orderBy('ordre')
            ->get();
    }

    public function getCouleurCssAttribute(): string
    {
        return match ($this->couleur) {
            'blue'   => 'background:rgb(59 130 246)',
            'orange' => 'background:rgb(249 115 22)',
            'green'  => 'background:rgb(34 197 94)',
            'teal'   => 'background:rgb(20 184 166)',
            'mint'   => 'background:rgb(0 206 201)',
            'red'    => 'background:rgb(239 68 68)',
            'yellow' => 'background:rgb(234 179 8)',
            'purple' => 'background:rgb(168 85 247)',
            'pink'   => 'background:rgb(236 72 153)',
            default  => 'background:rgb(156 163 175)',
        };
    }

    public function getCouleurFilamentAttribute(): string
    {
        return match ($this->couleur) {
            'blue'   => 'info',
            'orange' => 'warning',
            'green'  => 'success',
            'teal'   => 'success',
            'mint'   => 'success',
            'red'    => 'danger',
            'yellow' => 'warning',
            'purple' => 'primary',
            'pink'   => 'danger',
            default  => 'gray',
        };
    }
}
