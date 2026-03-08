<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Detection;

use LaravelLocalLlm\Contracts\DriverInterface;
use LaravelLocalLlm\Drivers\AirLLMLlamaDriver;
use LaravelLocalLlm\Drivers\LMStudioDriver;
use LaravelLocalLlm\Drivers\OllamaDriver;
use LaravelLocalLlm\Drivers\OpenAICompatibleDriver;
use LaravelLocalLlm\Enums\Driver;

class DriverDetector
{
    /**
     * @return array<int, DriverInterface>
     */
    public function detect(): array
    {
        $drivers = [];
        $timeout = config('llm.detection.timeout', 1000);

        $driverClasses = [
            Driver::OLLAMA => OllamaDriver::class,
            Driver::LM_STUDIO => LMStudioDriver::class,
            Driver::OPENAI_COMPATIBLE => OpenAICompatibleDriver::class,
            Driver::AIRLLM_LLAMA => AirLLMLlamaDriver::class,
        ];

        foreach ($driverClasses as $driverEnum => $driverClass) {
            $config = config('llm.drivers.' . $driverEnum->value, []);

            if (!($config['enabled'] ?? true)) {
                continue;
            }

            $driver = new $driverClass();

            try {
                if ($driver->health()) {
                    $drivers[] = $driver;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $drivers;
    }

    public function isDriverAvailable(Driver $driver): bool
    {
        $driverClass = match ($driver) {
            Driver::OLLAMA => OllamaDriver::class,
            Driver::LM_STUDIO => LMStudioDriver::class,
            Driver::OPENAI_COMPATIBLE => OpenAICompatibleDriver::class,
            Driver::AIRLLM_LLAMA => AirLLMLlamaDriver::class,
        };

        $instance = new $driverClass();

        return $instance->health();
    }
}
