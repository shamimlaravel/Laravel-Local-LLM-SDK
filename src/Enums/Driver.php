<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Enums;

enum Driver: string
{
    case OLLAMA = 'ollama';
    case LM_STUDIO = 'lmstudio';
    case OPENAI_COMPATIBLE = 'openai-compatible';
    case AIRLLM_LLAMA = 'airllm-llama';

    public function displayName(): string
    {
        return match ($this) {
            self::OLLAMA => 'Ollama',
            self::LM_STUDIO => 'LM Studio',
            self::OPENAI_COMPATIBLE => 'OpenAI Compatible',
            self::AIRLLM_LLAMA => 'AirLLMLlama',
        };
    }

    public function defaultPort(): int
    {
        return match ($this) {
            self::OLLAMA => 11434,
            self::LM_STUDIO => 1234,
            self::OPENAI_COMPATIBLE => 8080,
            self::AIRLLM_LLAMA => 8080,
        };
    }

    public function healthEndpoint(): string
    {
        return match ($this) {
            self::OLLAMA => '/api/tags',
            self::LM_STUDIO => '/v1/models',
            self::OPENAI_COMPATIBLE => '/v1/models',
            self::AIRLLM_LLAMA => '/v1/models',
        };
    }

    public function chatEndpoint(): string
    {
        return match ($this) {
            self::OLLAMA => '/api/chat',
            self::LM_STUDIO => '/v1/chat/completions',
            self::OPENAI_COMPATIBLE => '/v1/chat/completions',
            self::AIRLLM_LLAMA => '/v1/chat/completions',
        };
    }

    public function streamEndpoint(): string
    {
        return $this->chatEndpoint();
    }
}
