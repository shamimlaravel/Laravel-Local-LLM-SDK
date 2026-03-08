<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Builders;

use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\Enums\Driver;
use LaravelLocalLlm\Enums\Role;

class ChatBuilder
{
    private string $model;

    private array $messages = [];

    private float $temperature = 0.7;

    private ?int $maxTokens = null;

    private bool $stream = false;

    private ?string $stop = null;

    private ?Driver $driver = null;

    private array $tools = [];

    public function __construct(
        private readonly \LaravelLocalLlm\Services\LocalLlmService $service,
    ) {}

    public function model(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function withSystemMessage(string $content): self
    {
        $this->messages[] = Message::system($content);
        return $this;
    }

    public function withUserMessage(string $content): self
    {
        $this->messages[] = Message::user($content);
        return $this;
    }

    public function withAssistantMessage(string $content): self
    {
        $this->messages[] = Message::assistant($content);
        return $this;
    }

    public function withMessage(Role $role, string $content): self
    {
        $this->messages[] = new Message($role, $content);
        return $this;
    }

    public function withMessages(array $messages): self
    {
        foreach ($messages as $message) {
            if ($message instanceof Message) {
                $this->messages[] = $message;
            } elseif (is_array($message)) {
                $this->messages[] = new Message(
                    Role::from($message['role']),
                    $message['content']
                );
            }
        }

        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function maxTokens(?int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function stream(bool $stream = true): self
    {
        $this->stream = $stream;
        return $this;
    }

    public function stop(string $stop): self
    {
        $this->stop = $stop;
        return $this;
    }

    public function driver(Driver $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    public function withTools(array $tools): self
    {
        $this->tools = $tools;
        return $this;
    }

    public function send(): \LaravelLocalLlm\DTO\ChatResponse
    {
        $request = new ChatRequest(
            model: $this->model,
            messages: $this->messages,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            stream: false,
            stop: $this->stop,
            tools: $this->tools,
        );

        return $this->service->chat($request, $this->driver);
    }

    public function sendStream(callable $onChunk): void
    {
        $request = new ChatRequest(
            model: $this->model,
            messages: $this->messages,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            stream: true,
            stop: $this->stop,
            tools: $this->tools,
        );

        $this->service->stream($request, $onChunk, $this->driver);
    }
}
