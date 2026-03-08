<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

readonly final class BatchChatResponse
{
    /**
     * @param array<int, ChatResponse> $responses
     */
    public function __construct(
        public array $responses,
    ) {}

    public function count(): int
    {
        return count($this->responses);
    }

    public function totalPromptTokens(): int
    {
        return array_sum(
            array_map(fn($r) => $r->promptTokens, $this->responses)
        );
    }

    public function totalCompletionTokens(): int
    {
        return array_sum(
            array_map(fn($r) => $r->completionTokens, $this->responses)
        );
    }

    public function totalTokens(): int
    {
        return $this->totalPromptTokens() + $this->totalCompletionTokens();
    }

    public function totalLatencyMs(): float
    {
        return array_sum(
            array_map(fn($r) => $r->latencyMs, $this->responses)
        );
    }

    public function averageLatencyMs(): float
    {
        if ($this->count() === 0) {
            return 0.0;
        }

        return $this->totalLatencyMs() / $this->count();
    }

    public function isEmpty(): bool
    {
        return empty($this->responses);
    }
}
