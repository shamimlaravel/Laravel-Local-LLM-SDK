<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Console\Commands;

use Illuminate\Console\Command;
use LaravelLocalLlm\Services\ModelCache;

class LlmClearCacheCommand extends Command
{
    protected $signature = 'llm:clear-cache {--models : Clear only models cache} {--health : Clear only health cache} {--all : Clear all caches (default)}';

    protected $description = 'Clear LLM cache (models, health)';

    public function handle(): int
    {
        $cache = new ModelCache();

        if ($this->option('models')) {
            $this->clearModelsCache($cache);
        } elseif ($this->option('health')) {
            $this->clearHealthCache($cache);
        } else {
            $this->clearAllCache($cache);
        }

        return Command::SUCCESS;
    }

    private function clearModelsCache(ModelCache $cache): void
    {
        $cache->invalidateAll();
        $this->info('Models cache cleared');
    }

    private function clearHealthCache(ModelCache $cache): void
    {
        $cache->invalidateAll();
        $this->info('Health cache cleared');
    }

    private function clearAllCache(ModelCache $cache): void
    {
        $cache->invalidateAll();
        $this->info('All LLM caches cleared');
    }
}
