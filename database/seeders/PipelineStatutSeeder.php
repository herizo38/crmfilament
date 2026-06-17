<?php

namespace Database\Seeders;

use App\Models\PipelineStatut;
use Illuminate\Database\Seeder;

class PipelineStatutSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/pipeline_statuts.php');

        foreach ($data as $modelType => $statuts) {
            foreach ($statuts as $statut) {
                PipelineStatut::updateOrCreate(
                    ['model_type' => $modelType, 'code' => $statut['code']],
                    array_merge($statut, [
                        'model_type' => $modelType,
                        'actif' => true,
                        'is_terminal' => $statut['is_terminal'] ?? false,
                        'is_archive' => $statut['is_archive'] ?? false,
                    ])
                );
            }
        }
    }
}
