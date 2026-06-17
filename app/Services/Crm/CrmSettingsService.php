<?php

namespace App\Services\Crm;

use App\Models\CrmSetting;
use Illuminate\Support\Facades\Cache;

class CrmSettingsService
{
    private const CACHE_KEY = 'crm_settings_all';

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            return CrmSetting::query()
                ->orderBy('groupe')
                ->orderBy('ordre')
                ->get()
                ->groupBy('groupe')
                ->map(fn ($items) => $items->mapWithKeys(
                    fn (CrmSetting $s) => [$s->cle => $s->casted_value]
                )->toArray())
                ->toArray();
        });
    }

    public function get(string $dottedKey, mixed $default = null): mixed
    {
        [$groupe, $cle] = array_pad(explode('.', $dottedKey, 2), 2, null);

        if (! $cle) {
            return $this->all()[$groupe] ?? $default;
        }

        return $this->all()[$groupe][$cle] ?? $default;
    }

    public function groupe(string $groupe): array
    {
        return $this->all()[$groupe] ?? [];
    }

    public function rolesCapablesDe(string $capability): array
    {
        $key = match ($capability) {
            'validate_qf' => 'team_leader_roles',
            'supervisor' => 'supervisor_roles',
            default => $capability,
        };

        return $this->get("qf.{$key}")
            ?? $this->get("roles.{$key}")
            ?? [];
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
