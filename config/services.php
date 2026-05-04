<?php

return [
    'reverb' => [
        'app_id' => env('REVERB_APP_ID'),
        'app_key' => env('REVERB_APP_KEY'),
        'app_secret' => env('REVERB_APP_SECRET'),
        'host' => env('REVERB_HOST', '127.0.0.1'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],
    'whatsapp' => [
        'base_url' => env('URL_WA_SERVER'),
        'api_key' => env('WA_TOKEN'),
        'sender' => env('WA_NUMBER'),
    ],
];
