<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Contracts;

interface UsageTrackerInterface
{
    public function record(
        int $tokenId,
        string $driver,
        string $model,
        int $promptTokens,
        int $completionTokens,
        float $latencyMs,
    ): void;

    public function getUsage(int $tokenId, ?string $driver = null, ?string $model = null): int;

    public function getMonthlyUsage(int $tokenId): int;
}
