<?php

return [
    'mail' => [
        'from_address' => env('AOPIA_MAIL_FROM', 'assistante-commerciale@ns-conseil.com'),
        'from_name' => env('AOPIA_MAIL_FROM_NAME', 'AOPIA Formation'),
        'mail2_locked_cc' => array_filter(array_map('trim', explode(',', env('AOPIA_MAIL2_CC', 'bruno@ns-conseil.com,nirina@ns-conseil.com')))),
        'send_deadline_minutes' => (int) env('AOPIA_MAIL_DEADLINE_MINUTES', 30),
    ],

    'qf' => [
        'minimum_employee_count' => (int) env('AOPIA_QF_MIN_EMPLOYEES', 12),
        'team_leader_roles' => ['team_leader', 'administrateur', 'super_admin'],
    ],

    'prospection' => [
        'max_standard_attempts' => (int) env('AOPIA_MAX_STANDARD_ATTEMPTS', 3),
        'std_nr_reminder_days' => (int) env('AOPIA_STD_NR_REMINDER_DAYS', 2),
        'rpc_delay_hours' => (int) env('AOPIA_RPC_DELAY_HOURS', 48),
    ],
];
