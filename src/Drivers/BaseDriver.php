<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Drivers;

use LaravelLocalLlm\Contracts\DriverInterface;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\DTO\EmbeddingRequest;
use LaravelLocalLlm\DTO\EmbeddingResponse;
use LaravelLocalLlm\DTO\ModelInfo;
use LaravelLocalLlm\Enums\Driver;
use LaravelLocalLlm\Exceptions\DriverException;

abstract class BaseDriver implements DriverInterface
{
    protected bool $enabled = true;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    protected function makeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        bool $stream = false,
    ): string|array {
        $url = rtrim($this->getConfig()['url'], '/') . $endpoint;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getConfig()['timeout'] ?? 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);

            if ($stream) {
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, $data) {
                    echo $data;
                    return strlen($data);
                });
            }
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \RuntimeException('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($stream) {
            return '';
        }

        return json_decode($response, true);
    }

    protected function getConfig(): array
    {
        return config('llm.drivers.' . $this->getDriver()->value, []);
    }

    protected function getDefaultModel(): string
    {
        return $this->getConfig()['default_model'] ?? 'llama3.2';
    }

    public function models(): array
    {
        return [];
    }

    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        throw new DriverException(
            sprintf('Embeddings not supported by %s driver', $this->getDriver()->value)
        );
    }
}
