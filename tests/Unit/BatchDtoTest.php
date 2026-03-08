<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelLocalLlm\DTO\BatchChatRequest;
use LaravelLocalLlm\DTO\BatchChatResponse;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\Enums\Driver;
use LaravelLocalLlm\Enums\Role;

class BatchDtoTest extends TestCase
{
    public function test_batch_request_creation(): void
    {
        $requests = [
            new ChatRequest(
                model: 'llama3.2',
                messages: [Message::user('Hello')]
            ),
            new ChatRequest(
                model: 'llama3.2',
                messages: [Message::user('Hi')]
            ),
        ];

        $batch = new BatchChatRequest($requests);

        $this->assertCount(2, $batch->requests);
        $this->assertFalse($batch->isEmpty());
    }

    public function test_empty_batch(): void
    {
        $batch = new BatchChatRequest([]);

        $this->assertTrue($batch->isEmpty());
        $this->assertSame(0, $batch->count());
    }

    public function test_batch_response_stats(): void
    {
        $responses = [
            new ChatResponse(
                content: 'Response 1',
                promptTokens: 10,
                completionTokens: 20,
                latencyMs: 100,
                driver: Driver::OLLAMA,
                model: 'llama3.2'
            ),
            new ChatResponse(
                content: 'Response 2',
                promptTokens: 15,
                completionTokens: 25,
                latencyMs: 150,
                driver: Driver::OLLAMA,
                model: 'llama3.2'
            ),
        ];

        $batch = new BatchChatResponse($responses);

        $this->assertSame(2, $batch->count());
        $this->assertSame(25, $batch->totalPromptTokens());
        $this->assertSame(45, $batch->totalCompletionTokens());
        $this->assertSame(70, $batch->totalTokens());
        $this->assertSame(250.0, $batch->totalLatencyMs());
        $this->assertSame(125.0, $batch->averageLatencyMs());
    }

    public function test_empty_batch_response(): void
    {
        $batch = new BatchChatResponse([]);

        $this->assertTrue($batch->isEmpty());
        $this->assertSame(0, $batch->totalTokens());
        $this->assertSame(0.0, $batch->averageLatencyMs());
    }
}
