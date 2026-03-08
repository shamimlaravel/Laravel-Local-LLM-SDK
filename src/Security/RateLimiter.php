<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Security;

final class LlmRateLimiter
{
    private const CACHE_PREFIX = 'llm_rate_limit:';

    public function __construct(
        private readonly \Illuminate\Cache\CacheManager $cache,
    ) {}

    public function attempt(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        $current = (int) $this->cache->store()->get($cacheKey, 0);

        if ($current >= $maxAttempts) {
            return false;
        }

        $this->cache->store()->put($cacheKey, $current + 1, $decaySeconds);

        return true;
    }

    public function remaining(string $key, int $maxAttempts): int
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $current = (int) $this->cache->store()->get($cacheKey, 0);

        return max(0, $maxAttempts - $current);
    }

    public function reset(string $key): void
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $this->cache->store()->forget($cacheKey);
    }

    public function retriesLeft(string $key, int $maxAttempts): int
    {
        return $this->remaining($key, $maxAttempts);
    }

    public function availableIn(string $key): int
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $ttl = $this->cache->store()->ttl($cacheKey);

        return $ttl > 0 ? $ttl : 0;
    }
}
