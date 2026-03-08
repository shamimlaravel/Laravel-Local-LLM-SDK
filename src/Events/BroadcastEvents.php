<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\DTO\StreamChunk;
use LaravelLocalLlm\Enums\Driver;

class StreamChunkBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $sessionId,
        public ChatRequest $request,
        public StreamChunk $chunk,
        public Driver $driver,
    ) {}

    public function broadcastOn(): array
    {
        return ['llm-stream'];
    }

    public function broadcastAs(): string
    {
        return 'chunk';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'content' => $this->chunk->content,
            'delta' => $this->chunk->delta,
            'finished' => $this->chunk->finished,
            'model' => $this->chunk->model,
            'finish_reason' => $this->chunk->finishReason,
            'prompt_tokens' => $this->chunk->promptTokens,
            'completion_tokens' => $this->chunk->completionTokens,
        ];
    }
}

class ChatCompletedBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $sessionId,
        public ChatRequest $request,
        public ChatResponse $response,
        public Driver $driver,
    ) {}

    public function broadcastOn(): array
    {
        return ['llm-chat'];
    }

    public function broadcastAs(): string
    {
        return 'completed';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'content' => $this->response->content,
            'model' => $this->response->model,
            'usage' => [
                'prompt_tokens' => $this->response->promptTokens,
                'completion_tokens' => $this->response->completionTokens,
                'total_tokens' => $this->response->totalTokens(),
            ],
            'latency_ms' => $this->response->latencyMs,
            'driver' => $this->driver->value,
        ];
    }
}
