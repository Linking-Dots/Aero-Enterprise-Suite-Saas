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
            'retries'     => (int) env('GEMINI_RETRIES', 2),        // extra tries on 429/503
            'retry_base_ms' => (int) env('GEMINI_RETRY_BASE_MS', 500), // backoff base (×attempt)
            // Tried in order after the primary model when it is 429/503 (busy/overloaded).
            'fallback_models' => env('GEMINI_FALLBACK_MODELS', 'gemini-2.5-flash,gemini-2.5-flash-lite'),
            'embed_model' => env('GEMINI_EMBED_MODEL', 'gemini-embedding-001'),
            'embed_dims'  => (int) env('GEMINI_EMBED_DIMS', 768),
        ],
    ],

    'ui' => [
        'floating_button' => env('AEON_FLOATING_BUTTON', true),
    ],
];
