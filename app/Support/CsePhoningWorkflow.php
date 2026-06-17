<?php

namespace App\Support;

use App\Models\StatutPhoning;
use App\Models\WorkflowGroupe;
use App\Services\Crm\CrmSettingsService;

/**
 * Workflow CSE — lecture 100 % base de données (statut_phonings + workflow_groupes).
 */
class CsePhoningWorkflow
{
    public static function ringoverRule(): string
    {
        return app(CrmSettingsService::class)->get(
            'prospection.ringover_rule',
            'DEP_XX + tag statut obligatoires par appel'
        );
    }

    public static function statutsGroupesPourProspect(): array
    {
        $statuts = StatutPhoning::forModelType('prospect');
        $groupes = WorkflowGroupe::forModelType('prospect')->keyBy('code');
        $grouped = [];

        foreach ($statuts as $statut) {
            $groupe = $statut->groupe ?: 'autre';
            $label = $statut->groupe_label
                ?? $groupes->get($groupe)?->label
                ?? 'Autres';

            if (! isset($grouped[$groupe])) {
                $grouped[$groupe] = ['label' => $label, 'statuts' => []];
            }

            $grouped[$groupe]['statuts'][] = [
                'value' => $statut->code,
                'label' => $statut->label,
                'sub' => $statut->description,
                'action' => $statut->action_immediate,
                'couleur' => $statut->couleur,
                'bar' => $statut->couleur_css,
                'icon' => $statut->icone,
                'note_obligatoire' => $statut->note_obligatoire,
                'prioritaire' => $statut->prioritaire,
                'fiche_type' => $statut->fiche_type,
            ];
        }

        $ordre = WorkflowGroupe::forModelType('prospect')->pluck('code')->toArray();
        $ordre[] = 'autre';

        $sorted = [];
        foreach ($ordre as $key) {
            if (isset($grouped[$key])) {
                $sorted[$key] = $grouped[$key];
            }
        }

        return $sorted;
    }
}
