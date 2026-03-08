<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Console\Commands;

use Illuminate\Console\Command;
use LaravelLocalLlm\Facades\LocalLlm;

class LlmHealthCheckCommand extends Command
{
    protected $signature = 'llm:health {--driver= : Specific driver to check}';

    protected $description = 'Check LLM driver health status';

    public function handle(): int
    {
        $driver = $this->option('driver');

        if ($driver) {
            return $this->checkDriver($driver);
        }

        return $this->checkAllDrivers();
    }

    private function checkDriver(string $driver): int
    {
        $this->info("Checking {$driver}...");

        $healthy = LocalLlm::health(\LaravelLocalLlm\Enums\Driver::from($driver));

        if ($healthy) {
            $this->info("✓ {$driver} is healthy");

            return Command::SUCCESS;
        }

        $this->error("✗ {$driver} is not responding");

        return Command::FAILURE;
    }

    private function checkAllDrivers(): int
    {
        $this->info('Checking all LLM drivers...');
        $this->newLine();

        $drivers = [
            'ollama' => \LaravelLocalLlm\Enums\Driver::OLLAMA,
            'lmstudio' => \LaravelLocalLlm\Enums\Driver::LM_STUDIO,
            'openai-compatible' => \LaravelLocalLlm\Enums\Driver::OPENAI_COMPATIBLE,
            'airllm-llama' => \LaravelLocalLlm\Enums\Driver::AIRLLM_LLAMA,
        ];

        $allHealthy = true;

        foreach ($drivers as $name => $driver) {
            $healthy = LocalLlm::health($driver);

            if ($healthy) {
                $this->info("✓ {$name}: healthy");
            } else {
                $this->error("✗ {$name}: not responding");
                $allHealthy = false;
            }
        }

        $this->newLine();

        return $allHealthy ? Command::SUCCESS : Command::FAILURE;
    }
}
