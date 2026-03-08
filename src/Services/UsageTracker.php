<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Services;

use LaravelLocalLlm\Contracts\UsageTrackerInterface;
use LaravelLocalLlm\Models\LlmUsage;

final class UsageTracker implements UsageTrackerInterface
{
    public function record(
        int $tokenId,
        string $driver,
        string $model,
        int $promptTokens,
        int $completionTokens,
        float $latencyMs,
    ): void {
        LlmUsage::create([
            'token_id' => $tokenId,
            'driver' => $driver,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'latency_ms' => $latencyMs,
        ]);
    }

    public function getUsage(int $tokenId, ?string $driver = null, ?string $model = null): int
    {
        $query = LlmUsage::where('token_id', $tokenId);

        if ($driver !== null) {
            $query->where('driver', $driver);
        }

        if ($model !== null) {
            $query->where('model', $model);
        }

        return (int) $query->sum('prompt_tokens + completion_tokens');
    }

    public function getMonthlyUsage(int $tokenId): int
    {
        return (int) LlmUsage::where('token_id', $tokenId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('prompt_tokens + completion_tokens');
    }

    public function getUsageBreakdown(int $tokenId): array
    {
        $byDriver = LlmUsage::where('token_id', $tokenId)
            ->selectRaw('driver, SUM(prompt_tokens + completion_tokens) as total')
            ->groupBy('driver')
            ->pluck('total', 'driver')
            ->toArray();

        $byModel = LlmUsage::where('token_id', $tokenId)
            ->selectRaw('model, SUM(prompt_tokens + completion_tokens) as total')
            ->groupBy('model')
            ->pluck('total', 'model')
            ->toArray();

        return [
            'by_driver' => array_map('intval', $byDriver),
            'by_model' => array_map('intval', $byModel),
            'total' => $this->getUsage($tokenId),
            'monthly' => $this->getMonthlyUsage($tokenId),
        ];
    }

    public function getAverageLatency(int $tokenId, ?string $driver = null): float
    {
        $query = LlmUsage::where('token_id', $tokenId);

        if ($driver !== null) {
            $query->where('driver', $driver);
        }

        return (float) $query->avg('latency_ms');
    }

    public function getRequestCount(int $tokenId, ?string $driver = null): int
    {
        $query = LlmUsage::where('token_id', $tokenId);

        if ($driver !== null) {
            $query->where('driver', $driver);
        }

        return (int) $query->count();
    }
}
