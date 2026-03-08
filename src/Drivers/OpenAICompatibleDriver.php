<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Drivers;

use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\DTO\EmbeddingRequest;
use LaravelLocalLlm\DTO\EmbeddingResponse;
use LaravelLocalLlm\DTO\Embedding;
use LaravelLocalLlm\DTO\ModelInfo;
use LaravelLocalLlm\DTO\StreamChunk;
use LaravelLocalLlm\Enums\Driver;

class OpenAICompatibleDriver extends BaseDriver
{
    public function getDriver(): Driver
    {
        return Driver::OPENAI_COMPATIBLE;
    }

    protected function getConfig(): array
    {
        return parent::getConfig();
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $startTime = microtime(true);

        $payload = [
            'model' => $request->model,
            'messages' => array_map(
                static fn($msg) => $msg->toArray(),
                $request->messages
            ),
            'temperature' => $request->temperature,
            'stream' => false,
        ];

        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        if ($request->stop !== null) {
            $payload['stop'] = [$request->stop];
        }

        $response = $this->makeRequest('POST', '/v1/chat/completions', $payload);

        $latencyMs = (microtime(true) - $startTime) * 1000;

        return new ChatResponse(
            content: $response['choices'][0]['message']['content'] ?? '',
            promptTokens: $response['usage']['prompt_tokens'] ?? 0,
            completionTokens: $response['usage']['completion_tokens'] ?? 0,
            latencyMs: $latencyMs,
            driver: $this->getDriver(),
            model: $response['model'] ?? $request->model,
        );
    }

    public function stream(ChatRequest $request, callable $onChunk): void
    {
        $payload = [
            'model' => $request->model,
            'messages' => array_map(
                static fn($msg) => $msg->toArray(),
                $request->messages
            ),
            'temperature' => $request->temperature,
            'stream' => true,
        ];

        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        $url = rtrim($this->getConfig()['url'], '/') . '/v1/chat/completions';

        $apiKey = $this->getConfig()['api_key'] ?? 'not-needed';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, $data) use ($onChunk) {
            $lines = explode("\n", trim($data));

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                if (!str_starts_with($line, 'data: ')) {
                    continue;
                }

                $line = substr($line, 6);

                if ($line === '[DONE]') {
                    $onChunk(new StreamChunk(
                        content: '',
                        finished: true,
                        finishReason: 'stop',
                    ));
                    continue;
                }

                $decoded = json_decode($line, true);

                if (!$decoded) {
                    continue;
                }

                $content = $decoded['choices'][0]['delta']['content'] ?? '';
                $finishReason = $decoded['choices'][0]['finish_reason'] ?? null;

                $onChunk(new StreamChunk(
                    content: $content,
                    finished: $finishReason !== null,
                    model: $decoded['model'] ?? null,
                    finishReason: $finishReason,
                    promptTokens: $decoded['usage']['prompt_tokens'] ?? null,
                    completionTokens: $decoded['usage']['completion_tokens'] ?? null,
                ));
            }

            return strlen($data);
        });

        curl_exec($ch);
        curl_close($ch);
    }

    public function models(): array
    {
        try {
            $response = $this->makeRequest('GET', '/v1/models', []);

            return array_map(
                static fn(array $model) => ModelInfo::fromArray([
                    'id' => $model['id'] ?? $model['id'] ?? 'unknown',
                    'name' => $model['id'] ?? null,
                ]),
                $response['data'] ?? []
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public function health(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/v1/models', []);
            return isset($response['data']);
        } catch (\Throwable) {
            return false;
        }
    }

    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        $inputs = is_array($request->input) ? $request->input : [$request->input];
        $embeddings = [];

        foreach ($inputs as $index => $text) {
            $payload = [
                'model' => $request->model,
                'input' => $text,
            ];

            $response = $this->makeRequest('POST', '/v1/embeddings', $payload);

            $embeddings[] = new Embedding(
                index: $index,
                embedding: $response['data'][0]['embedding'] ?? [],
            );
        }

        return new EmbeddingResponse(
            embeddings: $embeddings,
            model: $request->model,
            totalTokens: count($embeddings) * 384,
        );
    }
}
