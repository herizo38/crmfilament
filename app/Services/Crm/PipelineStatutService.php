<?php

namespace App\Services\Crm;

use App\Models\PipelineStatut;

class PipelineStatutService
{
    public function label(string $modelType, string $code): string
    {
        $statut = PipelineStatut::where('model_type', $modelType)
            ->where('code', $code)
            ->first();

        return $statut?->label ?? $code;
    }

    public function transitions(string $modelType, string $code): array
    {
        $statut = PipelineStatut::where('model_type', $modelType)
            ->where('code', $code)
            ->first();

        return $statut?->transitions ?? [];
    }

    public function canTransition(string $modelType, string $from, string $to): bool
    {
        $allowed = $this->transitions($modelType, $from);

        if (empty($allowed)) {
            return true;
        }

        return in_array($to, $allowed, true);
    }

    public function options(string $modelType): array
    {
        return PipelineStatut::optionsFor($modelType);
    }
}
