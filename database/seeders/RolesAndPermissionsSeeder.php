<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionsNS = [
            'partenaires.view_any', 'partenaires.view', 'partenaires.create', 'partenaires.update', 'partenaires.delete',
            'prospects.view_any', 'prospects.view', 'prospects.create', 'prospects.update', 'prospects.valider_qf',
            'clients.view_any', 'clients.view', 'clients.create', 'clients.update',
            'activites.create', 'activites.update', 'rapports.view', 'rapports.export',
        ];

        $permissionsAP = [
            'tickets.create', 'tickets.view', 'tickets.update_statut',
            'fiche_p2.create', 'fiche_p2.view', 'fiche_p2.update',
            'artisans.view', 'artisans.update',
            'reclamations.view', 'reclamations.create', 'reclamations.valider',
            'rapports_satisfaction.create',
            'prospection_artisans.create', 'prospection_artisans.update',
            'dashboard.temps_reel',
        ];

        foreach (array_merge($permissionsNS, $permissionsAP) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->call([
            CrmProfileSeeder::class,
        ]);

        $this->command->info('Rôles, profils et permissions synchronisés depuis database/seeders/data/crm_profiles.php');
    }
}
