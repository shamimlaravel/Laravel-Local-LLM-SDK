<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Webhooks;

use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\Enums\Driver;

class WebhookDispatcher
{
    private array $webhooks = [];

    public function register(string $event, string $url, array $options = []): self
    {
        $this->webhooks[$event][] = [
            'url' => $url,
            'secret' => $options['secret'] ?? null,
            'timeout' => $options['timeout'] ?? 30,
            'headers' => $options['headers'] ?? [],
        ];

        return $this;
    }

    public function dispatch(string $event, array $payload): void
    {
        if (!isset($this->webhooks[$event])) {
            return;
        }

        foreach ($this->webhooks[$event] as $webhook) {
            $this->sendWebhook($webhook, $event, $payload);
        }
    }

    public function dispatchChatCompleted(ChatRequest $request, ChatResponse $response, Driver $driver): void
    {
        $this->dispatch('chat.completed', [
            'event' => 'chat.completed',
            'timestamp' => now()->toIso8601String(),
            'driver' => $driver->value,
            'request' => [
                'model' => $request->model,
                'message_count' => count($request->messages),
                'temperature' => $request->temperature,
                'max_tokens' => $request->maxTokens,
            ],
            'response' => [
                'content' => $response->content,
                'model' => $response->model,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
                'total_tokens' => $response->totalTokens(),
                'latency_ms' => $response->latencyMs,
            ],
        ]);
    }

    public function dispatchChatError(ChatRequest $request, \Throwable $error, Driver $driver): void
    {
        $this->dispatch('chat.error', [
            'event' => 'chat.error',
            'timestamp' => now()->toIso8601String(),
            'driver' => $driver->value,
            'request' => [
                'model' => $request->model,
                'message_count' => count($request->messages),
            ],
            'error' => [
                'message' => $error->getMessage(),
                'class' => get_class($error),
                'code' => $error->getCode(),
            ],
        ]);
    }

    public function dispatchDriverHealthChange(Driver $driver, bool $isHealthy): void
    {
        $this->dispatch('driver.health_changed', [
            'event' => 'driver.health_changed',
            'timestamp' => now()->toIso8601String(),
            'driver' => $driver->value,
            'healthy' => $isHealthy,
        ]);
    }

    private function sendWebhook(array $webhook, string $event, array $payload): void
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'X-LLM-Webhook-Event' => $event,
        ], $webhook['headers']);

        $body = json_encode($payload);

        if ($webhook['secret'] !== null) {
            $signature = hash_hmac('sha256', $body, $webhook['secret']);
            $headers['X-LLM-Webhook-Signature'] = $signature;
        }

        $ch = curl_init($webhook['url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(
            fn($key, $value) => "$key: $value",
            array_keys($headers),
            $headers
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, $webhook['timeout']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);
        curl_close($ch);
    }

    public function getRegisteredWebhooks(): array
    {
        return $this->webhooks;
    }

    public function clear(string $event = null): self
    {
        if ($event === null) {
            $this->webhooks = [];
        } else {
            unset($this->webhooks[$event]);
        }

        return $this;
    }
}
