<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WorkflowGroupe extends Model
{
    protected $fillable = [
        'model_type',
        'code',
        'label',
        'ordre',
        'actif',
    ];

    protected $casts = [
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
}
