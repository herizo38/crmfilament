<?php

return [
    'api_id' => env('AIRCALL_ID'),
    'api_token' => env('AIRCALL_TOKEN'),
    'base_url' => env('AIRCALL_BASE_URL', 'https://api.aircall.io/v1'),
    'timeout' => env('AIRCALL_TIMEOUT', 10),
];