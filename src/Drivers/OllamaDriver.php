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

class OllamaDriver extends BaseDriver
{
    public function getDriver(): Driver
    {
        return Driver::OLLAMA;
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
            $payload['num_predict'] = $request->maxTokens;
        }

        if ($request->stop !== null) {
            $payload['stop'] = [$request->stop];
        }

        $response = $this->makeRequest('POST', '/api/chat', $payload);

        $latencyMs = (microtime(true) - $startTime) * 1000;

        return new ChatResponse(
            content: $response['message']['content'] ?? '',
            promptTokens: $response['prompt_eval_count'] ?? 0,
            completionTokens: $response['eval_count'] ?? 0,
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
            $payload['num_predict'] = $request->maxTokens;
        }

        $url = rtrim($this->getConfig()['url'], '/') . '/api/chat';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, $data) use ($onChunk, &$finished, &$totalContent) {
            $lines = explode("\n", trim($data));

            foreach ($lines as $line) {
                if (empty($line) || $line === 'done') {
                    continue;
                }

                if (str_starts_with($line, 'data: ')) {
                    $line = substr($line, 6);
                }

                $decoded = json_decode($line, true);

                if (!$decoded) {
                    continue;
                }

                $content = $decoded['message']['content'] ?? '';
                $done = $decoded['done'] ?? false;

                if ($content || $done) {
                    $onChunk(new StreamChunk(
                        content: $content,
                        finished: $done,
                        model: $decoded['model'] ?? null,
                        finishReason: $done ? 'stop' : null,
                        promptTokens: $decoded['prompt_eval_count'] ?? null,
                        completionTokens: $decoded['eval_count'] ?? null,
                    ));
                }
            }

            return strlen($data);
        });

        curl_exec($ch);
        curl_close($ch);
    }

    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        $inputs = is_array($request->input) ? $request->input : [$request->input];
        $embeddings = [];

        foreach ($inputs as $index => $text) {
            $payload = [
                'model' => $request->model,
                'prompt' => $text,
            ];

            $response = $this->makeRequest('POST', '/api/embeddings', $payload);

            $embeddings[] = new Embedding(
                index: $index,
                embedding: $response['embedding'] ?? [],
            );
        }

        return new EmbeddingResponse(
            embeddings: $embeddings,
            model: $request->model,
            totalTokens: count($embeddings) * 384,
        );
    }

    public function models(): array
    {
        try {
            $response = $this->makeRequest('GET', '/api/tags', []);

            return array_map(
                static fn(array $model) => ModelInfo::fromArray($model),
                $response['models'] ?? []
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public function health(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/api/tags', []);
            return isset($response['models']);
        } catch (\Throwable) {
            return false;
        }
    }
}
