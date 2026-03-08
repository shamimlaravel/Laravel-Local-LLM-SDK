<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

use LaravelLocalLlm\Enums\Driver;

readonly final class ChatResponse
{
    public function __construct(
        public string $content,
        public int $promptTokens,
        public int $completionTokens,
        public float $latencyMs,
        public Driver $driver,
        public string $model,
        public ?array $toolCalls = null,
        public ?array $messages = null,
        public ?string $finishReason = null,
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    public function toArray(): array
    {
        $data = [
            'content' => $this->content,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens(),
            'latency_ms' => $this->latencyMs,
            'driver' => $this->driver->value,
            'model' => $this->model,
        ];

        if ($this->toolCalls !== null) {
            $data['tool_calls'] = $this->toolCalls;
        }

        if ($this->messages !== null) {
            $data['messages'] = $this->messages;
        }

        if ($this->finishReason !== null) {
            $data['finish_reason'] = $this->finishReason;
        }

        return $data;
    }
}
