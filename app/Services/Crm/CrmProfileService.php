<?php

namespace App\Services\Crm;

use App\Models\CrmProfile;
use App\Models\User;
use Illuminate\Support\Collection;

class CrmProfileService
{
    public function all(): Collection
    {
        return CrmProfile::actifs()->get();
    }

    public function forPanel(string $panel): Collection
    {
        return CrmProfile::forPanel($panel)->get();
    }

    public function roleNamesForPanel(string $panel): array
    {
        return $this->forPanel($panel)->pluck('role_name')->toArray();
    }

    public function userCanAccessPanel(User $user, string $panel): bool
    {
        if (! $user->actif) {
            return false;
        }

        $roles = $user->roles->pluck('name')->toArray();

        if (empty($roles) && $user->role_cache) {
            $roles = [$user->role_cache];
        }

        return CrmProfile::query()
            ->where('actif', true)
            ->whereIn('role_name', $roles)
            ->whereJsonContains('panels', $panel)
            ->exists();
    }

    public function userHasCapability(User $user, string $capability): bool
    {
        $roles = $user->roles->pluck('name')->toArray();

        if (empty($roles) && $user->role_cache) {
            $roles = [$user->role_cache];
        }

        $query = CrmProfile::query()
            ->where('actif', true)
            ->whereIn('role_name', $roles);

        return match ($capability) {
            'validate_qf' => $query->where('can_validate_qf', true)->exists(),
            'import' => $query->where('can_import', true)->exists(),
            'supervisor' => $query->where('is_supervisor', true)->exists(),
            default => false,
        };
    }

    public function landingPathFor(User $user, string $panel): ?string
    {
        $roles = $user->roles->pluck('name')->toArray();

        $profile = CrmProfile::query()
            ->where('actif', true)
            ->whereIn('role_name', $roles)
            ->whereJsonContains('panels', $panel)
            ->orderBy('ordre')
            ->first();

        return $profile?->landing_path;
    }

    public function supervisorRoleNames(): array
    {
        return CrmProfile::query()
            ->where('actif', true)
            ->where('is_supervisor', true)
            ->pluck('role_name')
            ->toArray();
    }

    public function teleprospecteurRoleNames(): array
    {
        return CrmProfile::query()
            ->where('actif', true)
            ->where('role_name', 'like', '%teleprospecteur%')
            ->orWhere(fn ($q) => $q->where('role_name', 'teleprospecteur'))
            ->pluck('role_name')
            ->unique()
            ->values()
            ->toArray();
    }
}
