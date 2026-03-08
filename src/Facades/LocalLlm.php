<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Facades;

use Illuminate\Support\Facades\Facade;
use LaravelLocalLlm\Builders\ChatBuilder;
use LaravelLocalLlm\Contracts\DriverInterface;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\Enums\Driver;
use LaravelLocalLlm\Services\LocalLlmService;

/**
 * @method static ChatResponse chat(ChatRequest $request, ?Driver $driver = null)
 * @method static void stream(ChatRequest $request, callable $onChunk, ?Driver $driver = null)
 * @method static array models(?Driver $driver = null)
 * @method static bool health(?Driver $driver = null)
 * @method static array detectDrivers()
 * @method static ChatResponse chatWithFailover(ChatRequest $request)
 * @method static ChatBuilder chatWithBuilder()
 * @method static DriverInterface getDriver(?Driver $driver = null)
 * @method static self setDefaultDriver(Driver $driver)
 *
 * @see \LaravelLocalLlm\Services\LocalLlmService
 */
class LocalLlm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LocalLlmService::class;
    }
}
