<?php

namespace Database\Seeders;

use App\Models\CrmProfile;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CrmProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = require database_path('seeders/data/crm_profiles.php');

        foreach ($profiles as $profile) {
            CrmProfile::updateOrCreate(
                ['role_name' => $profile['role_name']],
                collect($profile)->except(['permissions', 'groupe'])->merge([
                    'actif' => $profile['actif'] ?? true,
                ])->toArray()
            );

            $this->syncRolePermissions($profile);
        }
    }

    private function syncRolePermissions(array $profile): void
    {
        $role = Role::firstOrCreate([
            'name' => $profile['role_name'],
            'guard_name' => 'web',
        ]);

        $perms = $profile['permissions'] ?? [];

        if ($perms === '*') {
            $role->syncPermissions(Permission::all());
            return;
        }

        if ($perms === 'allopro') {
            $role->syncPermissions(Permission::where('name', 'like', 'tickets.%')
                ->orWhere('name', 'like', 'fiche_p2.%')
                ->orWhere('name', 'like', 'artisans.%')
                ->orWhere('name', 'like', 'reclamations.%')
                ->orWhere('name', 'like', 'rapports_satisfaction.%')
                ->orWhere('name', 'like', 'prospection_artisans.%')
                ->orWhere('name', 'like', 'dashboard.%')
                ->get());
            return;
        }

        if (is_array($perms)) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            }
            $role->syncPermissions($perms);
        }
    }
}
