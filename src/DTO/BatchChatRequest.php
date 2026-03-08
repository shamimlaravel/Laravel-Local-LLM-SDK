<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

readonly final class BatchChatRequest
{
    /**
     * @param array<int, ChatRequest> $requests
     */
    public function __construct(
        public array $requests,
    ) {}

    public function count(): int
    {
        return count($this->requests);
    }

    public function isEmpty(): bool
    {
        return empty($this->requests);
    }
}
