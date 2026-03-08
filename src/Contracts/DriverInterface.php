<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Contracts;

use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\DTO\EmbeddingRequest;
use LaravelLocalLlm\DTO\EmbeddingResponse;
use LaravelLocalLlm\DTO\ModelInfo;
use LaravelLocalLlm\Enums\Driver;

interface DriverInterface
{
    public function getDriver(): Driver;

    public function chat(ChatRequest $request): ChatResponse;

    public function stream(ChatRequest $request, callable $onChunk): void;

    public function embeddings(EmbeddingRequest $request): EmbeddingResponse;

    public function models(): array;

    public function health(): bool;

    public function isEnabled(): bool;
}
