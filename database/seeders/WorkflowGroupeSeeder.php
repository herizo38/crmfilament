<?php

namespace Database\Seeders;

use App\Models\WorkflowGroupe;
use Illuminate\Database\Seeder;

class WorkflowGroupeSeeder extends Seeder
{
    public function run(): void
    {
        $groupes = require database_path('seeders/data/workflow_groupes.php');

        foreach ($groupes as $groupe) {
            WorkflowGroupe::updateOrCreate(
                ['model_type' => $groupe['model_type'], 'code' => $groupe['code']],
                array_merge($groupe, ['actif' => true])
            );
        }
    }
}
