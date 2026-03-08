<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Console\Commands;

use Illuminate\Console\Command;
use LaravelLocalLlm\Facades\LocalLlm;

class LlmListModelsCommand extends Command
{
    protected $signature = 'llm:models {--driver= : Specific driver to list models from}';

    protected $description = 'List available LLM models';

    public function handle(): int
    {
        $driverOption = $this->option('driver');

        if ($driverOption) {
            return $this->listModelsForDriver($driverOption);
        }

        return $this->listModelsForAllDrivers();
    }

    private function listModelsForDriver(string $driver): int
    {
        $this->info("Models for {$driver}:");

        $models = LocalLlm::models(\LaravelLocalLlm\Enums\Driver::from($driver));

        if (empty($models)) {
            $this->warn('No models found');

            return Command::SUCCESS;
        }

        $this->table(
            ['Name', 'ID', 'Size'],
            array_map(fn ($model) => [
                $model->name,
                $model->id,
                $this->formatSize($model->size ?? 0),
            ], $models)
        );

        return Command::SUCCESS;
    }

    private function listModelsForAllDrivers(): int
    {
        $drivers = [
            'ollama' => \LaravelLocalLlm\Enums\Driver::OLLAMA,
            'lmstudio' => \LaravelLocalLlm\Enums\Driver::LM_STUDIO,
            'openai-compatible' => \LaravelLocalLlm\Enums\Driver::OPENAI_COMPATIBLE,
            'airllm-llama' => \LaravelLocalLlm\Enums\Driver::AIRLLM_LLAMA,
        ];

        $hasModels = false;

        foreach ($drivers as $name => $driver) {
            $models = LocalLlm::models($driver);

            if (!empty($models)) {
                $hasModels = true;
                $this->info("{$name}:");

                $this->table(
                    ['Name', 'ID', 'Size'],
                    array_map(fn ($model) => [
                        $model->name,
                        $model->id,
                        $this->formatSize($model->size ?? 0),
                    ], $models)
                );

                $this->newLine();
            }
        }

        if (!$hasModels) {
            $this->warn('No models found for any driver');
        }

        return Command::SUCCESS;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes === 0) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }
}
