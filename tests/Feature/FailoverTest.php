<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use LaravelLocalLlm\Services\LocalLlmService;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\Enums\Driver;
use LaravelLocalLlm\Failover\FailoverManager;
use LaravelLocalLlm\Contracts\DriverInterface;

class FailoverTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\LaravelLocalLlm\LocalLlmServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('llm.default', 'ollama');
        $app['config']->set('llm.failover.enabled', true);
    }

    public function test_failover_uses_healthy_driver(): void
    {
        $mockDriver1 = $this->createMock(DriverInterface::class);
        $mockDriver1->method('getDriver')->willReturn(Driver::OLLAMA);
        $mockDriver1->method('health')->willReturn(true);
        $mockDriver1->expects($this->once())->method('chat');

        $mockDriver2 = $this->createMock(DriverInterface::class);
        $mockDriver2->method('getDriver')->willReturn(Driver::LM_STUDIO);
        $mockDriver2->method('health')->willReturn(false);

        $failoverManager = new FailoverManager([$mockDriver1, $mockDriver2]);

        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
        );

        $response = $failoverManager->execute($request);

        $this->assertNotNull($response);
    }

    public function test_failover_skips_disabled_drivers(): void
    {
        $mockDriver1 = $this->createMock(DriverInterface::class);
        $mockDriver1->method('getDriver')->willReturn(Driver::OLLAMA);
        $mockDriver1->method('health')->willReturn(true);
        $mockDriver1->method('isEnabled')->willReturn(false);

        $mockDriver2 = $this->createMock(DriverInterface::class);
        $mockDriver2->method('getDriver')->willReturn(Driver::LM_STUDIO);
        $mockDriver2->method('health')->willReturn(true);
        $mockDriver2->method('isEnabled')->willReturn(true);
        $mockDriver2->expects($this->once())->method('chat');

        $failoverManager = new FailoverManager([$mockDriver1, $mockDriver2]);

        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
        );

        $response = $failoverManager->execute($request);

        $this->assertNotNull($response);
    }

    public function test_failover_throws_when_no_drivers_available(): void
    {
        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver->method('getDriver')->willReturn(Driver::OLLAMA);
        $mockDriver->method('health')->willReturn(false);
        $mockDriver->method('isEnabled')->willReturn(true);

        $failoverManager = new FailoverManager([$mockDriver]);

        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
        );

        $this->expectException(\LaravelLocalLlm\Exceptions\NoAvailableDriverException::class);

        $failoverManager->execute($request);
    }

    public function test_failover_sorts_by_health(): void
    {
        $mockDriver1 = $this->createMock(DriverInterface::class);
        $mockDriver1->method('getDriver')->willReturn(Driver::OLLAMA);
        $mockDriver1->method('health')->willReturn(false);

        $mockDriver2 = $this->createMock(DriverInterface::class);
        $mockDriver2->method('getDriver')->willReturn(Driver::LM_STUDIO);
        $mockDriver2->method('health')->willReturn(true);
        $mockDriver2->expects($this->once())->method('chat');

        $failoverManager = new FailoverManager([$mockDriver1, $mockDriver2]);

        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
        );

        $response = $failoverManager->execute($request);

        $this->assertNotNull($response);
    }

    public function test_failover_add_driver(): void
    {
        $failoverManager = new FailoverManager();

        $mockDriver = $this->createMock(DriverInterface::class);

        $result = $failoverManager->addDriver($mockDriver);

        $this->assertSame($result, $failoverManager);
        $this->assertCount(1, $failoverManager->getDrivers());
    }

    public function test_failover_set_drivers(): void
    {
        $mockDriver1 = $this->createMock(DriverInterface::class);
        $mockDriver2 = $this->createMock(DriverInterface::class);

        $failoverManager = new FailoverManager();
        $failoverManager->setDrivers([$mockDriver1, $mockDriver2]);

        $this->assertCount(2, $failoverManager->getDrivers());
    }
}
