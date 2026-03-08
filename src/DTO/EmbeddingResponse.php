<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

readonly final class EmbeddingResponse
{
    /**
     * @param array<int, Embedding> $embeddings
     */
    public function __construct(
        public array $embeddings,
        public string $model,
        public int $totalTokens,
    ) {}

    public function toArray(): array
    {
        return [
            'embeddings' => array_map(
                static fn(Embedding $e) => $e->toArray(),
                $this->embeddings
            ),
            'model' => $this->model,
            'total_tokens' => $this->totalTokens,
        ];
    }
}
