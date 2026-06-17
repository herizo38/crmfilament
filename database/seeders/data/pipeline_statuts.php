<?php

return [
    'prospect' => [
        ['code' => 'AC', 'label' => 'À contacter', 'description' => 'Fiche affectée, aucun appel', 'couleur' => 'gray', 'icone' => 'heroicon-o-phone', 'ordre' => 1, 'transitions' => ['STD_NR', 'STD_Joint', 'KO']],
        ['code' => 'STD_NR', 'label' => 'Standard non répondu', 'couleur' => 'warning', 'icone' => 'heroicon-o-building-office', 'ordre' => 2, 'transitions' => ['STD_Joint', 'KO', 'AC']],
        ['code' => 'STD_Joint', 'label' => 'Standard joint', 'couleur' => 'info', 'icone' => 'heroicon-o-check-badge', 'ordre' => 3, 'transitions' => ['CSE_NR', 'RP', 'RPC', 'KO']],
        ['code' => 'CSE_NR', 'label' => 'CSE non répondu', 'couleur' => 'warning', 'icone' => 'heroicon-o-user-group', 'ordre' => 4, 'transitions' => ['RP', 'RPC', 'STD_Joint', 'KO']],
        ['code' => 'RP', 'label' => 'Rappel planifié', 'couleur' => 'success', 'icone' => 'heroicon-o-clock', 'ordre' => 5, 'transitions' => ['STD_Joint', 'CSE_NR', 'RPC', 'KO']],
        ['code' => 'RPC', 'label' => 'RDV à planifier', 'couleur' => 'success', 'icone' => 'heroicon-o-calendar-days', 'ordre' => 6, 'transitions' => ['RP', 'KO']],
        ['code' => 'KO', 'label' => 'Hors cible / Refus', 'couleur' => 'danger', 'icone' => 'heroicon-o-x-circle', 'ordre' => 7, 'is_archive' => true, 'transitions' => []],
        ['code' => 'QF', 'label' => 'RDV qualifié', 'couleur' => 'primary', 'icone' => 'heroicon-o-check-circle', 'ordre' => 8, 'is_terminal' => true, 'transitions' => []],
    ],
    'partenaire' => [
        ['code' => 'a_prospecter', 'label' => 'À prospecter', 'couleur' => 'gray', 'ordre' => 1, 'transitions' => ['en_cours_prospection', 'refus']],
        ['code' => 'en_cours_prospection', 'label' => 'En cours de prospection', 'couleur' => 'blue', 'ordre' => 2, 'transitions' => ['rdv_en_cours', 'signe_accord_cadre', 'refus']],
        ['code' => 'rdv_en_cours', 'label' => 'RDV en cours', 'couleur' => 'orange', 'ordre' => 3, 'transitions' => ['signe_accord_cadre', 'en_cours_prospection', 'refus']],
        ['code' => 'signe_accord_cadre', 'label' => 'Signé accord cadre', 'couleur' => 'green', 'ordre' => 4, 'transitions' => ['convention_engagement', 'refus']],
        ['code' => 'convention_engagement', 'label' => 'Convention d\'engagement', 'couleur' => 'emerald', 'ordre' => 5, 'is_terminal' => true, 'transitions' => []],
        ['code' => 'refus', 'label' => 'Refus', 'couleur' => 'red', 'ordre' => 6, 'is_archive' => true, 'transitions' => ['a_prospecter']],
    ],
    'opportunite' => [
        ['code' => 'nouveau', 'label' => 'Nouveau', 'ordre' => 1, 'transitions' => ['en_cours_evaluation', 'perdu']],
        ['code' => 'en_cours_evaluation', 'label' => 'En cours d\'évaluation', 'ordre' => 2, 'transitions' => ['qualifiee', 'perdu']],
        ['code' => 'qualifiee', 'label' => 'Qualifiée', 'ordre' => 3, 'transitions' => ['converti', 'perdu']],
        ['code' => 'converti', 'label' => 'Converti', 'ordre' => 4, 'is_terminal' => true, 'transitions' => []],
        ['code' => 'perdu', 'label' => 'Perdue', 'ordre' => 5, 'is_archive' => true, 'transitions' => []],
    ],
];
