<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Services;

use LaravelLocalLlm\DTO\ModelInfo;
use LaravelLocalLlm\Enums\Driver;

class ModelCache
{
    private const CACHE_PREFIX = 'llm_models_';

    private const HEALTH_PREFIX = 'llm_health_';

    public function getModels(Driver $driver): ?array
    {
        $key = self::CACHE_PREFIX . $driver->value;

        if (!$this->cacheAvailable()) {
            return null;
        }

        return cache()->get($key);
    }

    public function setModels(Driver $driver, array $models): void
    {
        $key = self::CACHE_PREFIX . $driver->value;
        $ttl = config('llm.detection.cache_ttl', 300);

        if ($this->cacheAvailable()) {
            cache()->put($key, $models, $ttl);
        }
    }

    public function getHealth(Driver $driver): ?bool
    {
        $key = self::HEALTH_PREFIX . $driver->value;

        if (!$this->cacheAvailable()) {
            return null;
        }

        return cache()->get($key);
    }

    public function setHealth(Driver $driver, bool $healthy): void
    {
        $key = self::HEALTH_PREFIX . $driver->value;
        $ttl = config('llm.detection.cache_ttl', 300);

        if ($this->cacheAvailable()) {
            cache()->put($key, $healthy, $ttl);
        }
    }

    public function invalidate(Driver $driver): void
    {
        $modelKey = self::CACHE_PREFIX . $driver->value;
        $healthKey = self::HEALTH_PREFIX . $driver->value;

        if ($this->cacheAvailable()) {
            cache()->forget($modelKey);
            cache()->forget($healthKey);
        }
    }

    public function invalidateAll(): void
    {
        $drivers = ['ollama', 'lmstudio', 'openai-compatible', 'airllm-llama'];

        foreach ($drivers as $driver) {
            $this->invalidate(Driver::from($driver));
        }
    }

    private function cacheAvailable(): bool
    {
        return app()->bound('cache');
    }
}
