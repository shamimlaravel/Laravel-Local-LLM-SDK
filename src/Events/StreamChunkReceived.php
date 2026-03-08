<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\StreamChunk;
use LaravelLocalLlm\Enums\Driver;

class StreamChunkReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatRequest $request,
        public StreamChunk $chunk,
        public ?Driver $driver = null,
    ) {}
}
