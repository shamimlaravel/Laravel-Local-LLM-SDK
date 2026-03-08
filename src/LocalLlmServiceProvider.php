<?php

declare(strict_types=1);

namespace LaravelLocalLlm;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use LaravelLocalLlm\Console\Commands\LlmClearCacheCommand;
use LaravelLocalLlm\Console\Commands\LlmHealthCheckCommand;
use LaravelLocalLlm\Console\Commands\LlmListModelsCommand;
use LaravelLocalLlm\Contracts\DriverInterface;
use LaravelLocalLlm\Contracts\GuardInterface;
use LaravelLocalLlm\Drivers\AirLLMLlamaDriver;
use LaravelLocalLlm\Drivers\LMStudioDriver;
use LaravelLocalLlm\Drivers\OllamaDriver;
use LaravelLocalLlm\Drivers\OpenAICompatibleDriver;
use LaravelLocalLlm\Guards\TokenGuard;
use LaravelLocalLlm\Services\LocalLlmService;

class LocalLlmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/llm.php',
            'llm'
        );

        $this->app->singleton(LocalLlmService::class, function ($app) {
            return new LocalLlmService();
        });

        $this->app->singleton(GuardInterface::class, function ($app) {
            return new TokenGuard();
        });

        $this->app->singleton(DriverInterface::class . '.ollama', function ($app) {
            return new OllamaDriver();
        });

        $this->app->singleton(DriverInterface::class . '.lmstudio', function ($app) {
            return new LMStudioDriver();
        });

        $this->app->singleton(DriverInterface::class . '.openai-compatible', function ($app) {
            return new OpenAICompatibleDriver();
        });

        $this->app->singleton(DriverInterface::class . '.airllm-llama', function ($app) {
            return new AirLLMLlamaDriver();
        });
    }

    private function loadRoutes(): void
    {
        if (file_exists($routesFile = __DIR__ . '/../routes/llm.php')) {
            require $routesFile;
        }
    }

    private function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LlmHealthCheckCommand::class,
                LlmListModelsCommand::class,
                LlmClearCacheCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadCommands();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/llm.php' => config_path('llm.php'),
            ], 'llm-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'llm-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
