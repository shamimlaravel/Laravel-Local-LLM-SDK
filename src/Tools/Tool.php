<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Tools;

use Closure;

abstract readonly class Tool
{
    abstract public function getName(): string;

    abstract public function getDescription(): string;

    /**
     * @return array<ToolParameter>
     */
    abstract public function getParameters(): array;

    abstract public function execute(array $arguments): mixed;

    public function toArray(): array
    {
        $parameters = [];
        $required = [];

        foreach ($this->getParameters() as $param) {
            $parameters[$param->name] = $param->toArray();

            if ($param->required) {
                $required[] = $param->name;
            }
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => $parameters,
                    'required' => $required,
                ],
            ],
        ];
    }
}

final readonly class CallableTool extends Tool
{
    /**
     * @param array<ToolParameter> $parameters
     * @param Closure(array): mixed $handler
     */
    public function __construct(
        private string $name,
        private string $description,
        private array $parameters,
        private Closure $handler,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<ToolParameter>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function execute(array $arguments): mixed
    {
        return ($this->handler)($arguments);
    }
}

final class ToolCall
{
    public function __construct(
        public string $id,
        public string $name,
        public array $arguments,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            arguments: is_string($data['arguments'])
                ? json_decode($data['arguments'], true)
                : ($data['arguments'] ?? []),
        );
    }
}

final class ToolResult
{
    public function __construct(
        public string $toolCallId,
        public mixed $result,
        public bool $isError = false,
    ) {}

    public function toContent(): string
    {
        if ($this->isError) {
            return json_encode([
                'error' => true,
                'message' => $this->result,
            ], JSON_THROW_ON_ERROR);
        }

        return is_string($this->result)
            ? $this->result
            : json_encode($this->result, JSON_THROW_ON_ERROR);
    }
}
