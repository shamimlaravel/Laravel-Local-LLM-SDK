<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

readonly final class EmbeddingRequest
{
    /**
     * @param array<int, string>|string $input
     */
    public function __construct(
        public string $model,
        public array|string $input,
    ) {}

    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'input' => $this->input,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            model: $data['model'],
            input: $data['input'],
        );
    }
}
