<?php

namespace Database\Seeders;

use App\Models\StatutPhoning;
use Illuminate\Database\Seeder;

class StatutPhoningSeeder extends Seeder
{
    public function run(): void
    {
        $statuts = [
            // ── Prospect CSE — 14 statuts Ringover ──────────────────────────
            // Cas 1 : Appel non abouti
            ['model_type' => 'prospect', 'code' => 'nrp',         'label' => 'NRP',       'description' => 'Ne répond pas',                             'couleur' => 'gray',   'icone' => '📵', 'ordre' => 1],
            ['model_type' => 'prospect', 'code' => 'fax',         'label' => 'FAX',       'description' => 'Fax / Numéro incorrect',                     'couleur' => 'gray',   'icone' => '📠', 'ordre' => 2],
            ['model_type' => 'prospect', 'code' => 'supp',        'label' => 'SUPP',      'description' => 'À supprimer — aucun numéro trouvé',           'couleur' => 'red',    'icone' => '🗑️', 'ordre' => 3],
            ['model_type' => 'prospect', 'code' => 'maj',         'label' => 'MAJ',       'description' => 'Numéro mis à jour par Nirina',               'couleur' => 'teal',   'icone' => '🔄', 'ordre' => 4],
            // Cas 2 : Élu CSE joint directement
            ['model_type' => 'prospect', 'code' => 'rdv',         'label' => 'RDV',       'description' => 'Rendez-vous confirmé avec l\'élu',           'couleur' => 'green',  'icone' => '📅', 'ordre' => 5],
            ['model_type' => 'prospect', 'code' => 'cse_ni',      'label' => 'CSE-NI',    'description' => 'Élu non intéressé — fiche jaune J+7',        'couleur' => 'yellow', 'icone' => '🟡', 'ordre' => 6],
            ['model_type' => 'prospect', 'code' => 'rapl_elu',    'label' => 'RAPL-ELU',  'description' => 'Rappel demandé par l\'élu — signal positif', 'couleur' => 'mint',   'icone' => '⏰', 'ordre' => 7],
            // Cas 3 : Blocage au standard
            ['model_type' => 'prospect', 'code' => 'rapl_std',    'label' => 'RAPL-STD',  'description' => 'Rappel suggéré par le standard — signal neutre', 'couleur' => 'mint', 'icone' => '⏱️', 'ordre' => 8],
            ['model_type' => 'prospect', 'code' => 'bloc',        'label' => 'BLOC',      'description' => 'Bloqué au standard — mail envoyé',            'couleur' => 'orange', 'icone' => '🔒', 'ordre' => 9],
            ['model_type' => 'prospect', 'code' => 'bloc2',       'label' => 'BLOC2',     'description' => 'Toujours bloqué — fiche verte commercial',    'couleur' => 'orange', 'icone' => '🔒', 'ordre' => 10],
            // Cas 4 : Pas de CSE
            ['model_type' => 'prospect', 'code' => 'ncse_50',     'label' => 'NCSE-50',   'description' => 'Pas de CSE — moins de 50 salariés',           'couleur' => 'blue',   'icone' => '🏢', 'ordre' => 11],
            ['model_type' => 'prospect', 'code' => 'ncse_plus50', 'label' => 'NCSE+50',   'description' => 'Pas de CSE — 50 sal. ou plus, insister',      'couleur' => 'purple', 'icone' => '🏛️', 'ordre' => 12],
            // Cas particulier : CSE centralisé
            ['model_type' => 'prospect', 'code' => 'cse_zone',    'label' => 'CSE-ZONE',  'description' => 'CSE centralisé dans notre zone',              'couleur' => 'pink',   'icone' => '📍', 'ordre' => 13],
            ['model_type' => 'prospect', 'code' => 'cse_hz',      'label' => 'CSE-HZ',    'description' => 'CSE centralisé hors zone — transmettre à Bruno', 'couleur' => 'pink', 'icone' => '🗺️', 'ordre' => 14],

            // ── Partenaire ──────────────────────────────────────────────────
            ['model_type' => 'partenaire', 'code' => 'std_nr',    'label' => 'STD-NR',    'description' => 'Standard sans réponse', 'couleur' => 'gray',  'icone' => '📵', 'ordre' => 1],
            ['model_type' => 'partenaire', 'code' => 'std_joint', 'label' => 'STD-Joint', 'description' => 'Standard joint',        'couleur' => 'blue',  'icone' => '📞', 'ordre' => 2],
            ['model_type' => 'partenaire', 'code' => 'cse_nr',    'label' => 'CSE-NR',    'description' => 'CSE sans réponse',      'couleur' => 'orange','icone' => '🟠', 'ordre' => 3],
            ['model_type' => 'partenaire', 'code' => 'rp',        'label' => 'RP',        'description' => 'Contact joint',         'couleur' => 'green', 'icone' => '✅', 'ordre' => 4],
            ['model_type' => 'partenaire', 'code' => 'rpc',       'label' => 'RPC',       'description' => 'RDV confirmé',          'couleur' => 'teal',  'icone' => '⭐', 'ordre' => 5],
            ['model_type' => 'partenaire', 'code' => 'ko',        'label' => 'KO',        'description' => 'Refus',                 'couleur' => 'red',   'icone' => '🚫', 'ordre' => 6],

            // ── Opportunité ─────────────────────────────────────────────────
            ['model_type' => 'opportunite', 'code' => 'std_nr',    'label' => 'STD-NR',    'description' => 'Sans réponse',    'couleur' => 'gray',   'icone' => '📵', 'ordre' => 1],
            ['model_type' => 'opportunite', 'code' => 'std_joint', 'label' => 'STD-Joint', 'description' => 'Standard joint',  'couleur' => 'blue',   'icone' => '📞', 'ordre' => 2],
            ['model_type' => 'opportunite', 'code' => 'cse_nr',    'label' => 'CSE-NR',    'description' => 'CSE sans réponse','couleur' => 'orange', 'icone' => '🟠', 'ordre' => 3],
            ['model_type' => 'opportunite', 'code' => 'rp',        'label' => 'RP',        'description' => 'Rappel planifié', 'couleur' => 'green',  'icone' => '✅', 'ordre' => 4],
            ['model_type' => 'opportunite', 'code' => 'rpc',       'label' => 'RPC',       'description' => 'RDV confirmé',    'couleur' => 'teal',   'icone' => '⭐', 'ordre' => 5],
            ['model_type' => 'opportunite', 'code' => 'ko',        'label' => 'KO',        'description' => 'Refus',           'couleur' => 'red',    'icone' => '🚫', 'ordre' => 6],

            // ── Client ──────────────────────────────────────────────────────
            ['model_type' => 'client', 'code' => 'std_nr', 'label' => 'Sans réponse', 'description' => 'Pas joignable',      'couleur' => 'gray',  'icone' => '📵', 'ordre' => 1],
            ['model_type' => 'client', 'code' => 'rp',     'label' => 'Rappel',       'description' => 'Rappel à planifier', 'couleur' => 'green', 'icone' => '📅', 'ordre' => 2],
            ['model_type' => 'client', 'code' => 'ko',     'label' => 'KO',           'description' => 'Ne plus contacter',  'couleur' => 'red',   'icone' => '🚫', 'ordre' => 3],
        ];

        foreach ($statuts as $statut) {
            StatutPhoning::updateOrCreate(
                ['model_type' => $statut['model_type'], 'code' => $statut['code']],
                array_merge($statut, ['actif' => true])
            );
        }

        // Désactiver les anciens codes prospect remplacés par les 14 statuts CSE
        StatutPhoning::where('model_type', 'prospect')
            ->whereIn('code', ['std_nr', 'std_joint', 'cse_nr', 'rp', 'rpc', 'ko'])
            ->update(['actif' => false]);
    }
}
