<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Drivers;

use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\DTO\ModelInfo;
use LaravelLocalLlm\DTO\StreamChunk;
use LaravelLocalLlm\Enums\Driver;

class AirLLMLlamaDriver extends BaseDriver
{
    public function getDriver(): Driver
    {
        return Driver::AIRLLM_LLAMA;
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
            $payload['stop'] = is_array($request->stop) ? $request->stop : [$request->stop];
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
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
}
