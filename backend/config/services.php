<?php

return [
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses'      => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'resend'   => ['key' => env('RESEND_KEY')],
    'slack'    => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), 'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL')]],

    // Phase 3: Google ADK / Gemini
    'google' => [
        'api_key'    => env('GOOGLE_AI_API_KEY'),
        'project_id' => env('GOOGLE_CLOUD_PROJECT'),
    ],

    // Phase 3: Activepieces bridge
    'activepieces' => [
        'url'     => env('ACTIVEPIECES_URL', 'http://activepieces:80'),
        'api_key' => env('ACTIVEPIECES_API_KEY'),
    ],

    // Phase 3: Qdrant vector DB
    'qdrant' => [
        'url'     => env('QDRANT_URL', 'http://qdrant:6333'),
        'api_key' => env('QDRANT_API_KEY'),
    ],

    // Phase 3: Container registry
    'registry' => [
        'url' => env('CONTAINER_REGISTRY_URL', 'ghcr.io/eamcp'),
    ],

    // Phase 3: LLM guardrail for prompt-injection second pass
    'guardrail' => [
        'enabled'  => env('GUARDRAIL_ENABLED', false),
        'provider' => env('GUARDRAIL_PROVIDER', 'openai'),
        'model'    => env('GUARDRAIL_MODEL', 'gpt-4o-mini'),
    ],
];
