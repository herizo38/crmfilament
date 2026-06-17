<?php

namespace Database\Seeders;

use App\Models\Artisan;
use App\Enums\CorpsDeMetier;
use App\Enums\CanalAlerte;
use App\Enums\StatutCompteArtisan;
use Illuminate\Database\Seeder;

class ArtisanSeeder extends Seeder
{
    public function run(): void
    {
        $artisans = [
            [
                'nom' => 'DUPONT',
                'prenom' => 'Jean',
                'siret' => '12345678901234',
                'corps_de_metier' => CorpsDeMetier::Plomberie->value,
                'zone_intervention' => '57, 54, 67',
                'telephone_principal' => '0612345678',
                'telephone_secondaire' => '0623456789',
                'email' => 'jean.dupont@plombier.fr',
                'canal_alerte' => CanalAlerte::LesDeux->value,
                'statut_compte' => StatutCompteArtisan::Actif->value,
                'agenda_disponibilites' => true,
                'date_souscription' => now(),
                'date_activation' => now(),
            ],
            [
                'nom' => 'MARTIN',
                'prenom' => 'Sophie',
                'siret' => '23456789012345',
                'corps_de_metier' => CorpsDeMetier::Electricite->value,
                'zone_intervention' => '75, 92, 93, 94',
                'telephone_principal' => '0698765432',
                'email' => 'sophie.martin@electricien.fr',
                'canal_alerte' => CanalAlerte::SMS->value,
                'statut_compte' => StatutCompteArtisan::Actif->value,
                'agenda_disponibilites' => true,
                'date_souscription' => now(),
                'date_activation' => now(),
            ],
            [
                'nom' => 'BERNARD',
                'prenom' => 'Pierre',
                'siret' => '34567890123456',
                'corps_de_metier' => CorpsDeMetier::Serrurerie->value,
                'zone_intervention' => '69, 38, 74, 73',
                'telephone_principal' => '0678901234',
                'telephone_secondaire' => '0689012345',
                'email' => 'pierre.bernard@serrurier.fr',
                'canal_alerte' => CanalAlerte::LesDeux->value,
                'statut_compte' => StatutCompteArtisan::Actif->value,
                'agenda_disponibilites' => true,
                'date_souscription' => now(),
                'date_activation' => now(),
            ],
            [
                'nom' => 'PETIT',
                'prenom' => 'Marie',
                'siret' => '45678901234567',
                'corps_de_metier' => CorpsDeMetier::Chauffage->value,
                'zone_intervention' => '33, 24, 47',
                'telephone_principal' => '0654321098',
                'email' => 'marie.petit@chauffagiste.fr',
                'canal_alerte' => CanalAlerte::Appel->value,
                'statut_compte' => StatutCompteArtisan::Actif->value,
                'agenda_disponibilites' => true,
                'date_souscription' => now(),
                'date_activation' => now(),
            ],
        ];

        foreach ($artisans as $a) {
            // 🔄 Utilisation de updateOrCreate pour éviter de casser la production
            $artisan = Artisan::updateOrCreate(
                ['siret' => $a['siret']], // Le critère d'identification unique
                $a                        // Les données à insérer ou mettre à jour
            );

            // Gestion sécurisée de l'affichage du label de l'enum
            $label = method_exists($artisan->corps_de_metier, 'label')
                ? $artisan->corps_de_metier->label()
                : $artisan->corps_de_metier->value;

            $this->command->line("  ✓ Artisan: {$artisan->prenom} {$artisan->nom} ({$label})");
        }

        $this->command->info('✅ ' . count($artisans) . ' artisans synchronisés');
    }
}
