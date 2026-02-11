<?php

declare(strict_types=1);

return [
    'tier' => env('CRISP_TIER', 'plugin'),
    'token_id' => env('CRISP_TOKEN_ID', ''),
    'token_key' => env('CRISP_TOKEN_KEY', ''),
    'signing_secret' => env('CRISP_SIGNING_SECRET', ''),
    'webhook_path' => env('CRISP_WEBHOOK_PATH', 'crisp'),
    'plugin_id' => env('CRISP_PLUGIN_ID', ''),

    'settings' => [
        'route_path' => env('CRISP_SETTINGS_PATH', 'crisp/settings'),
        'token_cache_ttl' => (int) env('CRISP_TOKEN_CACHE_TTL', 300),
        'allowed_frame_origins' => [
            'https://app.crisp.chat',
            'https://app.crisp.im',
        ],
    ],
];
