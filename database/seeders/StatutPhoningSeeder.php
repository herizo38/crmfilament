<?php

namespace Database\Seeders;

use App\Models\StatutPhoning;
use App\Models\WorkflowGroupe;
use Illuminate\Database\Seeder;

class StatutPhoningSeeder extends Seeder
{
    public function run(): void
    {
        $prospectStatuts = require database_path('seeders/data/statuts_phoning_prospect.php');
        $groupes = WorkflowGroupe::forModelType('prospect')->keyBy('code');

        foreach ($prospectStatuts as $statut) {
            $groupeCode = $statut['groupe'] ?? null;
            $groupe = $groupeCode ? $groupes->get($groupeCode) : null;

            StatutPhoning::updateOrCreate(
                ['model_type' => 'prospect', 'code' => $statut['code']],
                array_merge($statut, [
                    'model_type' => 'prospect',
                    'groupe_label' => $groupe?->label,
                    'actif' => true,
                    'note_obligatoire' => $statut['note_obligatoire'] ?? false,
                    'prioritaire' => $statut['prioritaire'] ?? false,
                    'retire_de_file' => $statut['retire_de_file'] ?? false,
                    'compte_comme_tentative' => $statut['compte_comme_tentative'] ?? false,
                ])
            );
        }

        $legacy = [
            ['model_type' => 'partenaire', 'code' => 'std_nr',    'label' => 'STD-NR',    'description' => 'Standard sans réponse', 'couleur' => 'gray',  'icone' => '📵', 'ordre' => 1],
            ['model_type' => 'partenaire', 'code' => 'std_joint', 'label' => 'STD-Joint', 'description' => 'Standard joint',        'couleur' => 'blue',  'icone' => '📞', 'ordre' => 2],
            ['model_type' => 'partenaire', 'code' => 'cse_nr',    'label' => 'CSE-NR',    'description' => 'CSE sans réponse',      'couleur' => 'orange','icone' => '🟠', 'ordre' => 3],
            ['model_type' => 'partenaire', 'code' => 'rp',        'label' => 'RP',        'description' => 'Contact joint',         'couleur' => 'green', 'icone' => '✅', 'ordre' => 4],
            ['model_type' => 'partenaire', 'code' => 'rpc',       'label' => 'RPC',       'description' => 'RDV confirmé',          'couleur' => 'teal',  'icone' => '⭐', 'ordre' => 5],
            ['model_type' => 'partenaire', 'code' => 'ko',        'label' => 'KO',        'description' => 'Refus',                 'couleur' => 'red',   'icone' => '🚫', 'ordre' => 6],
            ['model_type' => 'opportunite', 'code' => 'std_nr',    'label' => 'STD-NR',    'description' => 'Sans réponse',    'couleur' => 'gray',   'icone' => '📵', 'ordre' => 1],
            ['model_type' => 'opportunite', 'code' => 'std_joint', 'label' => 'STD-Joint', 'description' => 'Standard joint',  'couleur' => 'blue',   'icone' => '📞', 'ordre' => 2],
            ['model_type' => 'opportunite', 'code' => 'cse_nr',    'label' => 'CSE-NR',    'description' => 'CSE sans réponse','couleur' => 'orange', 'icone' => '🟠', 'ordre' => 3],
            ['model_type' => 'opportunite', 'code' => 'rp',        'label' => 'RP',        'description' => 'Rappel planifié', 'couleur' => 'green',  'icone' => '✅', 'ordre' => 4],
            ['model_type' => 'opportunite', 'code' => 'rpc',       'label' => 'RPC',       'description' => 'RDV confirmé',    'couleur' => 'teal',   'icone' => '⭐', 'ordre' => 5],
            ['model_type' => 'opportunite', 'code' => 'ko',        'label' => 'KO',        'description' => 'Refus',           'couleur' => 'red',    'icone' => '🚫', 'ordre' => 6],
            ['model_type' => 'client', 'code' => 'std_nr', 'label' => 'Sans réponse', 'description' => 'Pas joignable',      'couleur' => 'gray',  'icone' => '📵', 'ordre' => 1],
            ['model_type' => 'client', 'code' => 'rp',     'label' => 'Rappel',       'description' => 'Rappel à planifier', 'couleur' => 'green', 'icone' => '📅', 'ordre' => 2],
            ['model_type' => 'client', 'code' => 'ko',     'label' => 'KO',           'description' => 'Ne plus contacter',  'couleur' => 'red',   'icone' => '🚫', 'ordre' => 3],
        ];

        foreach ($legacy as $statut) {
            StatutPhoning::updateOrCreate(
                ['model_type' => $statut['model_type'], 'code' => $statut['code']],
                array_merge($statut, ['actif' => true])
            );
        }

        StatutPhoning::where('model_type', 'prospect')
            ->whereIn('code', ['std_nr', 'std_joint', 'cse_nr', 'rp', 'rpc', 'ko'])
            ->update(['actif' => false]);
    }
}
