<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelLocalLlm\Enums\Driver;
use LaravelLocalLlm\Enums\Role;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\DTO\ChatRequest;

class EnumsTest extends TestCase
{
    public function test_driver_enum_values(): void
    {
        $this->assertEquals('ollama', Driver::OLLAMA->value);
        $this->assertEquals('lmstudio', Driver::LM_STUDIO->value);
        $this->assertEquals('openai-compatible', Driver::OPENAI_COMPATIBLE->value);
        $this->assertEquals('airllm-llama', Driver::AIRLLM_LLAMA->value);
    }

    public function test_driver_display_name(): void
    {
        $this->assertEquals('Ollama', Driver::OLLAMA->displayName());
        $this->assertEquals('LM Studio', Driver::LM_STUDIO->displayName());
        $this->assertEquals('AirLLMLlama', Driver::AIRLLM_LLAMA->displayName());
    }

    public function test_driver_default_ports(): void
    {
        $this->assertEquals(11434, Driver::OLLAMA->defaultPort());
        $this->assertEquals(1234, Driver::LM_STUDIO->defaultPort());
        $this->assertEquals(8080, Driver::OPENAI_COMPATIBLE->defaultPort());
        $this->assertEquals(8080, Driver::AIRLLM_LLAMA->defaultPort());
    }

    public function test_role_enum_values(): void
    {
        $this->assertEquals('system', Role::SYSTEM->value);
        $this->assertEquals('user', Role::USER->value);
        $this->assertEquals('assistant', Role::ASSISTANT->value);
        $this->assertEquals('tool', Role::TOOL->value);
    }

    public function test_message_creation(): void
    {
        $message = Message::system('You are helpful');
        
        $this->assertEquals(Role::SYSTEM, $message->role);
        $this->assertEquals('You are helpful', $message->content);
    }

    public function test_message_to_array(): void
    {
        $message = Message::user('Hello');
        $array = $message->toArray();
        
        $this->assertEquals(['role' => 'user', 'content' => 'Hello'], $array);
    }

    public function test_chat_request_creation(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            temperature: 0.7,
        );
        
        $this->assertEquals('llama3.2', $request->model);
        $this->assertEquals(0.7, $request->temperature);
        $this->assertFalse($request->stream);
        $this->assertNull($request->maxTokens);
    }

    public function test_chat_request_to_array(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            temperature: 0.5,
        );
        
        $array = $request->toArray();
        
        $this->assertEquals('llama3.2', $array['model']);
        $this->assertEquals(0.5, $array['temperature']);
        $this->assertFalse($array['stream']);
    }
}
