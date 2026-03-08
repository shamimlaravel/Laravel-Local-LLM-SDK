<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

readonly final class ToolCall
{
    public function __construct(
        public string $id,
        public ToolCallFunction $function,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'function',
            'function' => [
                'name' => $this->function->name,
                'arguments' => $this->function->arguments,
            ],
        ];
    }
}

readonly final class ToolCallFunction
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public string $name,
        public array $arguments,
    ) {}
}
