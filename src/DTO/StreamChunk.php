<?php

declare(strict_types=1);

namespace LaravelLocalLlm\DTO;

readonly final class StreamChunk
{
    public function __construct(
        public string $content,
        public bool $finished,
        public ?string $model = null,
        public ?string $finishReason = null,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
    ) {}

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'finished' => $this->finished,
            'model' => $this->model,
            'finish_reason' => $this->finishReason,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
        ];
    }
}
