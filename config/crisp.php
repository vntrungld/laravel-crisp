<?php

declare(strict_types=1);

return [
    'tier' => env('CRISP_TIER', 'plugin'),
    'token_id' => env('CRISP_TOKEN_ID', ''),
    'token_key' => env('CRISP_TOKEN_KEY', ''),
    'signing_secret' => env('CRISP_SIGNING_SECRET', ''),
    'webhook_path' => env('CRISP_WEBHOOK_PATH', 'crisp'),
    'plugin_id' => env('CRISP_PLUGIN_ID', ''),
];
