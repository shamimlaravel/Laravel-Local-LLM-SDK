<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

readonly class Embedding
{
    public function __construct(
        public int $index,
        public array $embedding,
        public string $object = 'embedding',
    ) {}

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'embedding' => $this->embedding,
            'object' => $this->object,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            index: $data['index'] ?? 0,
            embedding: $data['embedding'] ?? [],
            object: $data['object'] ?? 'embedding',
        );
    }
}
