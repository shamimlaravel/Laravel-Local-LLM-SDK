<?php

declare(strict_types=1);

return [
    'default' => env('LLM_DEFAULT_DRIVER', 'ollama'),

    'defaults' => [
        'model' => env('LLM_DEFAULT_MODEL', 'llama3.2'),
        'temperature' => env('LLM_DEFAULT_TEMPERATURE', 0.7),
        'max_tokens' => env('LLM_DEFAULT_MAX_TOKENS', 2048),
        'timeout' => env('LLM_DEFAULT_TIMEOUT', 120),
    ],

    'auto_detect' => env('LLM_AUTO_DETECT', true),

    'failover' => [
        'enabled' => env('LLM_FAILOVER_ENABLED', true),
        'max_attempts' => env('LLM_FAILOVER_MAX_ATTEMPTS', 3),
    ],

    'drivers' => [
        'ollama' => [
            'enabled' => env('LLM_OLLAMA_ENABLED', true),
            'url' => env('LLM_OLLAMA_URL', 'http://localhost:11434'),
            'timeout' => env('LLM_OLLAMA_TIMEOUT', 120),
            'default_model' => env('LLM_OLLAMA_DEFAULT_MODEL', 'llama3.2'),
        ],

        'lmstudio' => [
            'enabled' => env('LLM_LMSTUDIO_ENABLED', true),
            'url' => env('LLM_LMSTUDIO_URL', 'http://localhost:1234/v1'),
            'timeout' => env('LLM_LMSTUDIO_TIMEOUT', 120),
            'default_model' => env('LLM_LMSTUDIO_DEFAULT_MODEL', 'llama-3.2-1b-instruct'),
        ],

        'openai-compatible' => [
            'enabled' => env('LLM_OPENAI_COMPATIBLE_ENABLED', false),
            'url' => env('LLM_OPENAI_COMPATIBLE_URL', 'http://localhost:8080/v1'),
            'timeout' => env('LLM_OPENAI_COMPATIBLE_TIMEOUT', 120),
            'api_key' => env('LLM_OPENAI_COMPATIBLE_API_KEY', 'not-needed'),
            'default_model' => env('LLM_OPENAI_COMPATIBLE_DEFAULT_MODEL', 'llama-3.2-1b-instruct'),
        ],

        'airllm-llama' => [
            'enabled' => env('LLM_AIRLLM_LLAMA_ENABLED', false),
            'url' => env('LLM_AIRLLM_LLAMA_URL', 'http://localhost:8080/v1'),
            'timeout' => env('LLM_AIRLLM_LLAMA_TIMEOUT', 120),
            'api_key' => env('LLM_AIRLLM_LLAMA_API_KEY', 'not-needed'),
            'default_model' => env('LLM_AIRLLM_LLAMA_DEFAULT_MODEL', 'llama-3.2-1b-instruct'),
        ],
    ],

    'detection' => [
        'timeout' => env('LLM_DETECTION_TIMEOUT', 1000),
        'cache_ttl' => env('LLM_DETECTION_CACHE_TTL', 300),
    ],

    'rate_limit' => [
        'default' => env('LLM_RATE_LIMIT_DEFAULT', 60),
        'window' => env('LLM_RATE_LIMIT_WINDOW', 60),
    ],

    'quota' => [
        'default' => env('LLM_QUOTA_DEFAULT', 1000000),
    ],

    'streaming' => [
        'timeout' => env('LLM_STREAMING_TIMEOUT', 300),
        'buffer_size' => env('LLM_STREAMING_BUFFER_SIZE', 1024),
    ],

    'retry' => [
        'max_attempts' => env('LLM_RETRY_MAX_ATTEMPTS', 3),
        'backoff_ms' => env('LLM_RETRY_BACKOFF_MS', 100),
        'multiplier' => env('LLM_RETRY_MULTIPLIER', 2),
    ],

    'logging' => [
        'enabled' => env('LLM_LOGGING_ENABLED', false),
        'log_messages' => env('LLM_LOGGING_LOG_MESSAGES', false),
        'log_content' => env('LLM_LOGGING_LOG_CONTENT', false),
    ],
];
