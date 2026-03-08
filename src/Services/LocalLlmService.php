<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Services;

use LaravelLocalLlm\Builders\ChatBuilder;
use LaravelLocalLlm\Contracts\DriverInterface;
use LaravelLocalLlm\Detection\DriverDetector;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\DTO\BatchChatRequest;
use LaravelLocalLlm\DTO\BatchChatResponse;
use LaravelLocalLlm\DTO\EmbeddingRequest;
use LaravelLocalLlm\DTO\EmbeddingResponse;
use LaravelLocalLlm\DTO\StreamChunk;
use LaravelLocalLlm\Enums\Driver;
use LaravelLocalLlm\Events\ChatCompleted;
use LaravelLocalLlm\Events\StreamChunkReceived;
use LaravelLocalLlm\Failover\FailoverManager;

class LocalLlmService
{
    protected ?Driver $defaultDriver = null;

    protected array $drivers = [];

    protected ?DriverDetector $detector = null;

    public function __construct(
        protected readonly ?FailoverManager $failoverManager = null,
    ) {}

    public function getDriver(?Driver $driver = null): DriverInterface
    {
        $driver ??= $this->getDefaultDriver();

        $driverClass = match ($driver) {
            Driver::OLLAMA => \LaravelLocalLlm\Drivers\OllamaDriver::class,
            Driver::LM_STUDIO => \LaravelLocalLlm\Drivers\LMStudioDriver::class,
            Driver::OPENAI_COMPATIBLE => \LaravelLocalLlm\Drivers\OpenAICompatibleDriver::class,
            Driver::AIRLLM_LLAMA => \LaravelLocalLlm\Drivers\AirLLMLlamaDriver::class,
        };

        return new $driverClass();
    }

    public function getDefaultDriver(): Driver
    {
        return $this->defaultDriver ?? Driver::from(config('llm.default', 'ollama'));
    }

    public function setDefaultDriver(Driver $driver): self
    {
        $this->defaultDriver = $driver;
        return $this;
    }

    public function registerDriver(DriverInterface $driver): self
    {
        $this->drivers[$driver->getDriver()->value] = $driver;
        return $this;
    }

    public function chat(ChatRequest $request, ?Driver $driver = null): ChatResponse
    {
        $driver ??= $this->getDefaultDriver();
        $driverInstance = $this->getDriver($driver);

        $response = $driverInstance->chat($request);

        event(new ChatCompleted($request, $response, $driver));

        return $response;
    }

    public function stream(ChatRequest $request, callable $onChunk, ?Driver $driver = null): void
    {
        $driver ??= $this->getDefaultDriver();
        $driverInstance = $this->getDriver($driver);

        $driverInstance->stream($request, function (StreamChunk $chunk) use ($request, $onChunk, $driver) {
            $onChunk($chunk);
            event(new StreamChunkReceived($request, $chunk, $driver));
        });
    }

    public function models(?Driver $driver = null): array
    {
        $driver ??= $this->getDefaultDriver();
        return $this->getDriver($driver)->models();
    }

    /**
     * @return array{total: int, per_page: int, current_page: int, last_page: int, data: array}
     */
    public function modelsPaginated(?Driver $driver = null, int $perPage = 15, int $page = 1): array
    {
        $driver ??= $this->getDefaultDriver();
        $allModels = $this->getDriver($driver)->models();

        $total = count($allModels);
        $lastPage = (int) ceil($total / $perPage);
        $page = min($page, max(1, $lastPage));

        $offset = ($page - 1) * $perPage;
        $data = array_slice($allModels, $offset, $perPage);

        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'data' => $data,
        ];
    }

    public function health(?Driver $driver = null): bool
    {
        $driver ??= $this->getDefaultDriver();
        return $this->getDriver($driver)->health();
    }

    public function detectDrivers(): array
    {
        $this->detector ??= new DriverDetector();
        return $this->detector->detect();
    }

    public function chatWithFailover(ChatRequest $request): ChatResponse
    {
        $failoverManager = $this->failoverManager ?? new FailoverManager($this->detectDrivers());

        $response = $failoverManager->execute($request);

        event(new ChatCompleted($request, $response, $response->driver));

        return $response;
    }

    public function chatWithBuilder(): ChatBuilder
    {
        return new ChatBuilder($this);
    }

    public function embeddings(EmbeddingRequest $request, ?Driver $driver = null): EmbeddingResponse
    {
        $driver ??= $this->getDefaultDriver();
        return $this->getDriver($driver)->embeddings($request);
    }

    public function batchChat(BatchChatRequest $batchRequest, ?Driver $driver = null): BatchChatResponse
    {
        $responses = [];

        foreach ($batchRequest->requests as $request) {
            $responses[] = $this->chat($request, $driver);
        }

        return new BatchChatResponse($responses);
    }
}
