<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Failover;

use LaravelLocalLlm\Contracts\DriverInterface;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\Exceptions\NoAvailableDriverException;

class FailoverManager
{
    /**
     * @param array<int, DriverInterface> $drivers
     */
    public function __construct(
        private array $drivers = [],
    ) {}

    public function addDriver(DriverInterface $driver): self
    {
        $this->drivers[] = $driver;
        return $this;
    }

    public function setDrivers(array $drivers): self
    {
        $this->drivers = $drivers;
        return $this;
    }

    public function getDrivers(): array
    {
        return $this->drivers;
    }

    public function execute(ChatRequest $request): ChatResponse
    {
        $drivers = $this->sortByHealth($this->drivers);

        $lastException = null;

        foreach ($drivers as $driver) {
            if (!$driver->isEnabled()) {
                continue;
            }

            try {
                if ($driver->health()) {
                    return $driver->chat($request);
                }
            } catch (\Throwable $e) {
                $lastException = $e;
                continue;
            }
        }

        throw new NoAvailableDriverException(
            'No available drivers could handle the request',
            previous: $lastException
        );
    }

    /**
     * @param array<int, DriverInterface> $drivers
     * @return array<int, DriverInterface>
     */
    private function sortByHealth(array $drivers): array
    {
        usort($drivers, static function (DriverInterface $a, DriverInterface $b) {
            $healthA = $a->health();
            $healthB = $b->health();

            if ($healthA === $healthB) {
                return 0;
            }

            return $healthA ? -1 : 1;
        });

        return $drivers;
    }
}
