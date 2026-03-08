<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

use LaravelLocalLlm\Enums\Driver;

readonly final class ChatRequest
{
    /**
     * @param array<int, Message> $messages
     * @param array<int, \LaravelLocalLlm\Tools\Tool> $tools
     */
    public function __construct(
        public string $model,
        public array $messages,
        public float $temperature = 0.7,
        public ?int $maxTokens = null,
        public bool $stream = false,
        public ?string $stop = null,
        public array $tools = [],
    ) {}

    public function toArray(): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => array_map(
                static fn(Message $message) => $message->toArray(),
                $this->messages
            ),
            'temperature' => $this->temperature,
        ];

        if ($this->maxTokens !== null) {
            $payload['max_tokens'] = $this->maxTokens;
        }

        $payload['stream'] = $this->stream;

        if ($this->stop !== null) {
            $payload['stop'] = $this->stop;
        }

        if ($this->tools !== []) {
            $payload['tools'] = array_map(
                static fn(\LaravelLocalLlm\Tools\Tool $tool) => $tool->toArray(),
                $this->tools
            );
        }

        return $payload;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            model: $data['model'],
            messages: array_map(
                static fn(array $msg) => new Message(
                    role: \LaravelLocalLlm\Enums\Role::from($msg['role']),
                    content: $msg['content']
                ),
                $data['messages']
            ),
            temperature: (float) ($data['temperature'] ?? 0.7),
            maxTokens: $data['max_tokens'] ?? null,
            stream: $data['stream'] ?? false,
            stop: $data['stop'] ?? null,
            tools: $data['tools'] ?? [],
        );
    }
}
