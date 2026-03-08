<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelLocalLlm\Detection\DriverDetector;
use LaravelLocalLlm\Drivers\OllamaDriver;
use LaravelLocalLlm\Drivers\LMStudioDriver;
use LaravelLocalLlm\Enums\Driver;

class DetectorTest extends TestCase
{
    public function test_driver_detector_is_available(): void
    {
        $detector = new DriverDetector();
        
        self::assertInstanceOf(DriverDetector::class, $detector);
    }

    public function test_driver_enum_for_detection(): void
    {
        self::assertEquals('ollama', Driver::OLLAMA->value);
        self::assertEquals('lmstudio', Driver::LM_STUDIO->value);
        self::assertEquals('openai-compatible', Driver::OPENAI_COMPATIBLE->value);
    }

    public function test_driver_display_names(): void
    {
        self::assertEquals('Ollama', Driver::OLLAMA->displayName());
        self::assertEquals('LM Studio', Driver::LM_STUDIO->displayName());
        self::assertEquals('OpenAI Compatible', Driver::OPENAI_COMPATIBLE->displayName());
    }
}
