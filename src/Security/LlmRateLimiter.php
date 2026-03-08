<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Security;

use Illuminate\Support\Facades\Cache;

final class LlmRateLimiter
{
    private const CACHE_PREFIX = 'llm_rate_limit:';

    public function attempt(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        $current = (int) Cache::get($cacheKey, 0);

        if ($current >= $maxAttempts) {
            return false;
        }

        Cache::put($cacheKey, $current + 1, $decaySeconds);

        return true;
    }

    public function remaining(string $key, int $maxAttempts): int
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $current = (int) Cache::get($cacheKey, 0);

        return max(0, $maxAttempts - $current);
    }

    public function reset(string $key): void
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        Cache::forget($cacheKey);
    }

    public function retriesLeft(string $key, int $maxAttempts): int
    {
        return $this->remaining($key, $maxAttempts);
    }

    public function availableIn(string $key): int
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $ttl = Cache::getStore()->getDefaultCacheTime();

        return $ttl > 0 ? $ttl : 0;
    }
}
