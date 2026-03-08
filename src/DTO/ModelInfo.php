<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

readonly final class ModelInfo
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $modifiedAt = null,
        public ?int $size = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? $data['name'] ?? 'unknown',
            name: $data['name'] ?? null,
            modifiedAt: $data['modified_at'] ?? $data['created_at'] ?? null,
            size: $data['size'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'modified_at' => $this->modifiedAt,
            'size' => $this->size,
        ];
    }
}
