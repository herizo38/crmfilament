<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AlloproUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // ── Responsables / Managers ──────────────────────────────
            [
                'nom'        => 'DUPONT',
                'prenom'     => 'Sophie',
                'email'      => 's.dupont@allopro.fr',
                'role'       => 'responsable_plateau',
                'description' => 'Responsable du plateau AlloPro 24/24',
            ],

            // ── Opérateurs N1 ─────────────────────────
            [
                'nom'        => 'LEFEBVRE',
                'prenom'     => 'Marc',
                'email'      => 'm.lefebvre@allopro.fr',
                'role'       => 'operateur_n1',
                'description' => 'Opérateur N1 - Gestion des appels entrants',
            ],
            [
                'nom'        => 'NGUYEN',
                'prenom'     => 'Linh',
                'email'      => 'l.nguyen@allopro.fr',
                'role'       => 'operateur_n1',
                'description' => 'Opérateur N1 - Gestion des appels entrants',
            ],
            [
                'nom'        => 'PETIT',
                'prenom'     => 'Thomas',
                'email'      => 't.petit@allopro.fr',
                'role'       => 'operateur_n1',
                'description' => 'Opérateur N1 - Gestion des appels entrants',
            ],
            [
                'nom'        => 'ROUX',
                'prenom'     => 'Camille',
                'email'      => 'c.roux@allopro.fr',
                'role'       => 'operateur_n1',
                'description' => 'Opérateur N1 - Gestion des appels entrants',
            ],

            // ── Back Office ────────────────────────────────
            [
                'nom'        => 'BERNARD',
                'prenom'     => 'Claire',
                'email'      => 'c.bernard@allopro.fr',
                'role'       => 'back_office',
                'description' => 'Back Office - Traitement des dossiers',
            ],
            [
                'nom'        => 'RICHARD',
                'prenom'     => 'Nicolas',
                'email'      => 'n.richard@allopro.fr',
                'role'       => 'back_office',
                'description' => 'Back Office - Gestion administrative',
            ],

            // ── Superviseurs (utilise responsable_plateau au lieu de superviseur) ──
            [
                'nom'        => 'DUBOIS',
                'prenom'     => 'Isabelle',
                'email'      => 'i.dubois@allopro.fr',
                'role'       => 'responsable_plateau',
                'description' => 'Superviseur - Contrôle qualité',
            ],
            [
                'nom'        => 'MORIN',
                'prenom'     => 'Philippe',
                'email'      => 'p.morin@allopro.fr',
                'role'       => 'responsable_plateau',
                'description' => 'Superviseur - Animation d\'équipe',
            ],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'nom'         => $u['nom'],
                    'prenom'      => $u['prenom'],
                    'secteur'     => null,
                    'password'    => Hash::make('allopro123'),
                    'actif'       => true,
                    'role_cache'  => $u['role'],
                    'email_verified_at' => now(),
                ]
            );

            // Assigner le rôle Spatie
            $user->syncRoles([$u['role']]);

            $this->command->line("  ✓ {$u['prenom']} {$u['nom']} ({$u['role']}) - {$u['email']}");
        }

        $this->command->newLine();
        $this->command->info('🎯 Seed AlloPro 24/24 terminé avec succès !');
        $this->command->info('📝 Identifiants par défaut :');
        $this->command->info('   • Mot de passe commun : allopro123');
    }
}
