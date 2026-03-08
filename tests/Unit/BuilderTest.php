<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelLocalLlm\Builders\ChatBuilder;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\Enums\Role;
use LaravelLocalLlm\Services\LocalLlmService;
use LaravelLocalLlm\Enums\Driver;

class BuilderTest extends TestCase
{
    public function test_builder_creation(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        self::assertInstanceOf(ChatBuilder::class, $builder);
    }

    public function test_builder_model(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->model('llama3.2');
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_with_system_message(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->withSystemMessage('You are helpful');
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_with_user_message(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->withUserMessage('Hello');
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_with_assistant_message(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->withAssistantMessage('Hi there');
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_with_message(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->withMessage(Role::USER, 'Hello');
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_temperature(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->temperature(0.9);
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_max_tokens(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->maxTokens(100);
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_max_tokens_null(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->maxTokens(null);
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_stream(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->stream(true);
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_stream_default(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->stream();
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_stop(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->stop('\n\n');
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_builder_driver(): void
    {
        $service = new LocalLlmService();
        $builder = new ChatBuilder($service);
        
        $result = $builder->driver(Driver::LM_STUDIO);
        
        self::assertInstanceOf(ChatBuilder::class, $result);
    }

    public function test_service_get_default_driver(): void
    {
        $service = new LocalLlmService();
        
        self::assertEquals(Driver::OLLAMA, $service->getDefaultDriver());
    }

    public function test_service_set_default_driver(): void
    {
        $service = new LocalLlmService();
        $service->setDefaultDriver(Driver::LM_STUDIO);
        
        self::assertEquals(Driver::LM_STUDIO, $service->getDefaultDriver());
    }
}
