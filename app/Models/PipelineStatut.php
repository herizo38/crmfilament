<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PipelineStatut extends Model
{
    protected $fillable = [
        'model_type',
        'code',
        'label',
        'description',
        'couleur',
        'icone',
        'transitions',
        'ordre',
        'is_terminal',
        'is_archive',
        'actif',
    ];

    protected $casts = [
        'transitions' => 'array',
        'is_terminal' => 'boolean',
        'is_archive' => 'boolean',
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    public static function forModelType(string $modelType): Collection
    {
        return static::where('model_type', $modelType)
            ->where('actif', true)
            ->orderBy('ordre')
            ->get();
    }

    public static function optionsFor(string $modelType): array
    {
        return static::forModelType($modelType)
            ->mapWithKeys(fn (self $s) => [$s->code => $s->label])
            ->toArray();
    }
}
