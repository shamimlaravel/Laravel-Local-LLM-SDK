<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

use LaravelLocalLlm\Enums\Role;

/**
 * @param array<int, ToolCall>|null $toolCalls
 */
readonly final class Message
{
    public function __construct(
        public Role $role,
        public string $content,
        public ?string $toolCallId = null,
        public ?array $toolCalls = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'role' => $this->role->value,
            'content' => $this->content,
        ];

        if ($this->toolCallId !== null) {
            $data['tool_call_id'] = $this->toolCallId;
        }

        if ($this->toolCalls !== null) {
            $data['tool_calls'] = array_map(
                static fn(ToolCall $tc) => $tc->toArray(),
                $this->toolCalls
            );
        }

        return $data;
    }

    public static function system(string $content): self
    {
        return new self(Role::SYSTEM, $content);
    }

    public static function user(string $content): self
    {
        return new self(Role::USER, $content);
    }

    public static function assistant(string $content, ?array $toolCalls = null): self
    {
        return new self(Role::ASSISTANT, $content, null, $toolCalls);
    }

    public static function tool(string $toolCallId, string $content): self
    {
        return new self(Role::TOOL, $content, $toolCallId);
    }
}
