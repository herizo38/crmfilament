<?php

/**
 * Paramètres CRM — remplace les valeurs statiques de config/aopia.php.
 */
return [
    ['groupe' => 'prospection', 'cle' => 'max_standard_attempts', 'valeur' => '3', 'type' => 'int', 'label' => 'Tentatives max au standard', 'description' => 'Nombre de tentatives NRP/FAX avant passage STD-NR', 'ordre' => 1],
    ['groupe' => 'prospection', 'cle' => 'std_nr_reminder_days', 'valeur' => '2', 'type' => 'int', 'label' => 'Relance STD-NR (jours)', 'description' => 'Délai de rappel après STD-NR', 'ordre' => 2],
    ['groupe' => 'prospection', 'cle' => 'rpc_delay_hours', 'valeur' => '48', 'type' => 'int', 'label' => 'Délai RPC (heures)', 'description' => 'Délai avant relance RPC sans date', 'ordre' => 3],
    ['groupe' => 'prospection', 'cle' => 'cse_ni_reminder_days', 'valeur' => '7', 'type' => 'int', 'label' => 'Relance CSE-NI (jours)', 'description' => 'Rappel commercial après élu non intéressé', 'ordre' => 4],
    ['groupe' => 'prospection', 'cle' => 'bloc_reminder_days', 'valeur' => '2', 'type' => 'int', 'label' => 'Relance BLOC (jours)', 'description' => 'Rappel après blocage standard', 'ordre' => 5],
    ['groupe' => 'prospection', 'cle' => 'ringover_rule', 'valeur' => 'DEP_XX + tag statut obligatoires par appel', 'type' => 'string', 'label' => 'Règle Ringover', 'description' => 'Règle d\'or tags téléphonie', 'ordre' => 6],

    ['groupe' => 'qf', 'cle' => 'minimum_employee_count', 'valeur' => '12', 'type' => 'int', 'label' => 'Effectif minimum QF', 'description' => 'Seuil salariés pour qualification', 'ordre' => 1],
    ['groupe' => 'qf', 'cle' => 'team_leader_roles', 'valeur' => '["team_leader","administrateur","super_admin"]', 'type' => 'json', 'label' => 'Rôles validateurs QF', 'description' => 'Profils autorisés à valider le statut QF', 'ordre' => 2],

    ['groupe' => 'roles', 'cle' => 'supervisor_roles', 'valeur' => '["super_admin","administrateur","responsable_plateau","superviseur","team_leader"]', 'type' => 'json', 'label' => 'Rôles superviseurs', 'description' => 'Accès mode supervision phoning', 'ordre' => 1],
    ['groupe' => 'roles', 'cle' => 'teleprospecteur_roles', 'valeur' => '["teleprospecteur"]', 'type' => 'json', 'label' => 'Rôles téléprospecteurs', 'description' => 'Profils affectés aux campagnes phoning', 'ordre' => 2],

    ['groupe' => 'mail', 'cle' => 'from_address', 'valeur' => 'assistante-commerciale@ns-conseil.com', 'type' => 'string', 'label' => 'Email expéditeur', 'description' => 'Adresse mails automatiques AOPIA', 'ordre' => 1],
    ['groupe' => 'mail', 'cle' => 'from_name', 'valeur' => 'AOPIA Formation', 'type' => 'string', 'label' => 'Nom expéditeur', 'description' => null, 'ordre' => 2],
    ['groupe' => 'mail', 'cle' => 'mail2_locked_cc', 'valeur' => '["bruno@ns-conseil.com","nirina@ns-conseil.com"]', 'type' => 'json', 'label' => 'CC invitation agenda', 'description' => 'Destinataires en copie Template 2', 'ordre' => 3],
    ['groupe' => 'mail', 'cle' => 'send_deadline_minutes', 'valeur' => '30', 'type' => 'int', 'label' => 'Délai envoi mail (min)', 'description' => 'Délai max après prise de RDV', 'ordre' => 4],
];
