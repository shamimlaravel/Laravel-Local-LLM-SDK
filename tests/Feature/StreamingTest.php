<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\DTO\StreamChunk;
use LaravelLocalLlm\Drivers\OllamaDriver;
use LaravelLocalLlm\Enums\Driver;

class StreamingTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\LaravelLocalLlm\LocalLlmServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('llm.drivers.ollama.url', 'http://localhost:11434');
    }

    public function test_stream_chunk_creation(): void
    {
        $chunk = new StreamChunk(
            content: 'Hello',
            finished: false,
        );

        $this->assertEquals('Hello', $chunk->content);
        $this->assertFalse($chunk->finished);
    }

    public function test_stream_chunk_finished(): void
    {
        $chunk = new StreamChunk(
            content: '',
            finished: true,
            finishReason: 'stop',
            model: 'llama3.2',
            promptTokens: 10,
            completionTokens: 5,
        );

        $this->assertTrue($chunk->finished);
        $this->assertEquals('stop', $chunk->finishReason);
        $this->assertEquals('llama3.2', $chunk->model);
        $this->assertEquals(10, $chunk->promptTokens);
        $this->assertEquals(5, $chunk->completionTokens);
    }

    public function test_stream_chunk_to_array(): void
    {
        $chunk = new StreamChunk(
            content: 'Test',
            finished: false,
        );

        $array = $chunk->toArray();

        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('finished', $array);
        $this->assertArrayHasKey('model', $array);
        $this->assertArrayHasKey('finish_reason', $array);
        $this->assertArrayHasKey('prompt_tokens', $array);
        $this->assertArrayHasKey('completion_tokens', $array);
    }

    public function test_stream_request_creation(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            stream: true,
        );

        $this->assertTrue($request->stream);
        $this->assertEquals('llama3.2', $request->model);
    }

    public function test_stream_request_to_array(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            stream: true,
        );

        $array = $request->toArray();

        $this->assertTrue($array['stream']);
    }

    public function test_driver_has_stream_endpoint(): void
    {
        $this->assertEquals('/api/chat', Driver::OLLAMA->streamEndpoint());
    }

    public function test_stream_chunk_with_partial_content(): void
    {
        $chunks = [
            new StreamChunk(content: 'Hello', finished: false),
            new StreamChunk(content: ' ', finished: false),
            new StreamChunk(content: 'World', finished: false),
            new StreamChunk(content: '', finished: true, finishReason: 'stop'),
        ];

        $fullContent = '';
        $finished = false;

        foreach ($chunks as $chunk) {
            $fullContent .= $chunk->content;
            if ($chunk->finished) {
                $finished = true;
            }
        }

        $this->assertEquals('Hello World', $fullContent);
        $this->assertTrue($finished);
    }

    public function test_stream_chunk_accumulates_tokens(): void
    {
        $chunks = [
            new StreamChunk(content: 'Hello', finished: false, promptTokens: 10, completionTokens: 1),
            new StreamChunk(content: ' World', finished: false, promptTokens: null, completionTokens: 2),
            new StreamChunk(content: '', finished: true, promptTokens: null, completionTokens: 3),
        ];

        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;

        foreach ($chunks as $chunk) {
            if ($chunk->promptTokens !== null) {
                $totalPromptTokens += $chunk->promptTokens;
            }
            if ($chunk->completionTokens !== null) {
                $totalCompletionTokens += $chunk->completionTokens;
            }
        }

        $this->assertEquals(10, $totalPromptTokens);
        $this->assertEquals(6, $totalCompletionTokens);
    }
}
