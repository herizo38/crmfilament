<?php

namespace App\Filament\NsConseil\Concerns;

trait HasRoleAccess
{
    protected static function userHasAnyRole(array $roles): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        foreach ($roles as $role) {
            if ($user->hasRoleCache($role)) {
                return true;
            }
        }

        return false;
    }
}
