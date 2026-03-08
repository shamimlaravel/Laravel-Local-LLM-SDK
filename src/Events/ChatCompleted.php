<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\Enums\Driver;

class ChatCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatRequest $request,
        public ChatResponse $response,
        public ?Driver $driver = null,
    ) {}
}
