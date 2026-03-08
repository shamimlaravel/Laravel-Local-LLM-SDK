<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Logging;

use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\Enums\Driver;

class RequestLogger
{
    public function logRequest(ChatRequest $request, Driver $driver): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        $context = [
            'driver' => $driver->value,
            'model' => $request->model,
            'message_count' => count($request->messages),
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
            'stream' => $request->stream,
        ];

        if (config('llm.logging.log_messages', false)) {
            $context['messages'] = array_map(
                fn($msg) => [
                    'role' => $msg->role->value,
                    'content' => $msg->content,
                ],
                $request->messages
            );
        }

        $this->log('info', 'LLM Request', $context);
    }

    public function logResponse(ChatResponse $response, float $durationMs): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        $context = [
            'driver' => $response->driver->value,
            'model' => $response->model,
            'content_length' => strlen($response->content),
            'prompt_tokens' => $response->promptTokens,
            'completion_tokens' => $response->completionTokens,
            'total_tokens' => $response->totalTokens(),
            'latency_ms' => $response->latencyMs,
            'duration_ms' => $durationMs,
        ];

        if (config('llm.logging.log_content', false)) {
            $context['content'] = $response->content;
        }

        $this->log('info', 'LLM Response', $context);
    }

    public function logError(ChatRequest $request, \Throwable $error, Driver $driver): void
    {
        $context = [
            'driver' => $driver->value,
            'model' => $request->model,
            'error' => $error->getMessage(),
            'error_class' => get_class($error),
        ];

        $this->log('error', 'LLM Error', $context);
    }

    private function shouldLog(): bool
    {
        return config('llm.logging.enabled', false);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (!app()->bound('log')) {
            return;
        }

        $logger = app('log');

        match ($level) {
            'debug' => $logger->debug($message, $context),
            'info' => $logger->info($message, $context),
            'warning' => $logger->warning($message, $context),
            'error' => $logger->error($message, $context),
            default => $logger->info($message, $context),
        };
    }
}
