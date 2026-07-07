<?php

return [
    'enabled'  => env('AEON_ENABLED', true),
    'provider' => env('AEON_PROVIDER', 'gemini'),

    'system_prompt' => env('AEON_SYSTEM_PROMPT',
        'You are Aeon, the built-in AI assistant for AEOS365, an enterprise ERP suite. '
        .'Be concise, accurate, and helpful. If you are unsure, say so.'),

    'providers' => [
        'gemini' => [
            'api_key'     => env('GEMINI_API_KEY'),
            'model'       => env('GEMINI_MODEL', 'gemini-flash-latest'),
            'endpoint'    => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
            'timeout'     => (int) env('GEMINI_TIMEOUT', 30),
            'embed_model' => env('GEMINI_EMBED_MODEL', 'gemini-embedding-001'),
            'embed_dims'  => (int) env('GEMINI_EMBED_DIMS', 768),
        ],
    ],

    'ui' => [
        'floating_button' => env('AEON_FLOATING_BUTTON', true),
    ],
];
