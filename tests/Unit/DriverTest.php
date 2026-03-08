<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelLocalLlm\Drivers\OllamaDriver;
use LaravelLocalLlm\Drivers\LMStudioDriver;
use LaravelLocalLlm\Drivers\OpenAICompatibleDriver;
use LaravelLocalLlm\Drivers\BaseDriver;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\Enums\Driver;

class DriverTest extends TestCase
{
    public function test_ollama_driver_get_driver(): void
    {
        $driver = new OllamaDriver();
        
        self::assertEquals(Driver::OLLAMA, $driver->getDriver());
    }

    public function test_lmstudio_driver_get_driver(): void
    {
        $driver = new LMStudioDriver();
        
        self::assertEquals(Driver::LM_STUDIO, $driver->getDriver());
    }

    public function test_openai_compatible_driver_get_driver(): void
    {
        $driver = new OpenAICompatibleDriver();
        
        self::assertEquals(Driver::OPENAI_COMPATIBLE, $driver->getDriver());
    }

    public function test_driver_is_enabled_by_default(): void
    {
        $driver = new OllamaDriver();
        
        self::assertTrue($driver->isEnabled());
    }

    public function test_driver_can_be_disabled(): void
    {
        $driver = new OllamaDriver();
        $driver->disable();
        
        self::assertFalse($driver->isEnabled());
    }

    public function test_driver_can_be_enabled(): void
    {
        $driver = new OllamaDriver();
        $driver->disable();
        $driver->enable();
        
        self::assertTrue($driver->isEnabled());
    }

    public function test_driver_returns_array_for_chat_request(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            temperature: 0.7,
        );
        
        $array = $request->toArray();
        
        self::assertIsArray($array);
        self::assertEquals('llama3.2', $array['model']);
        self::assertEquals(0.7, $array['temperature']);
    }

    public function test_driver_handles_system_message(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [
                Message::system('You are helpful'),
                Message::user('Hello'),
            ],
        );
        
        $array = $request->toArray();
        
        self::assertCount(2, $array['messages']);
        self::assertEquals('system', $array['messages'][0]['role']);
        self::assertEquals('You are helpful', $array['messages'][0]['content']);
    }

    public function test_driver_handles_assistant_message(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [
                Message::user('Hello'),
                Message::assistant('Hi there'),
            ],
        );
        
        $array = $request->toArray();
        
        self::assertCount(2, $array['messages']);
        self::assertEquals('assistant', $array['messages'][1]['role']);
    }

    public function test_driver_max_tokens(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            maxTokens: 100,
        );
        
        $array = $request->toArray();
        
        self::assertEquals(100, $array['max_tokens']);
    }

    public function test_driver_stop_sequence(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            stop: '\n\n',
        );
        
        $array = $request->toArray();
        
        self::assertEquals('\n\n', $array['stop']);
    }

    public function test_driver_health_endpoint(): void
    {
        self::assertEquals('/api/tags', Driver::OLLAMA->healthEndpoint());
        self::assertEquals('/v1/models', Driver::LM_STUDIO->healthEndpoint());
    }

    public function test_driver_chat_endpoint(): void
    {
        self::assertEquals('/api/chat', Driver::OLLAMA->chatEndpoint());
        self::assertEquals('/v1/chat/completions', Driver::LM_STUDIO->chatEndpoint());
    }
}
