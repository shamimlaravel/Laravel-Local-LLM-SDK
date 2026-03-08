<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Retry;

use InvalidArgumentException;

final class RetryStrategy
{
    private int $maxAttempts = 3;
    private int $baseDelayMs = 100;
    private float $multiplier = 2.0;
    private int $maxDelayMs = 10000;
    private bool $jitter = true;

    public function __construct() {}

    public static function default(): self
    {
        return new self();
    }

    public static function exponential(): self
    {
        return (new self())
            ->withMultiplier(2.0)
            ->withMaxDelayMs(30000);
    }

    public static function linear(): self
    {
        return (new self())
            ->withMultiplier(1.0);
    }

    public function withMaxAttempts(int $attempts): self
    {
        if ($attempts < 1) {
            throw new InvalidArgumentException('Max attempts must be at least 1');
        }

        $this->maxAttempts = $attempts;

        return $this;
    }

    public function withBaseDelayMs(int $delayMs): self
    {
        if ($delayMs < 0) {
            throw new InvalidArgumentException('Base delay must be non-negative');
        }

        $this->baseDelayMs = $delayMs;

        return $this;
    }

    public function withMultiplier(float $multiplier): self
    {
        if ($multiplier < 1.0) {
            throw new InvalidArgumentException('Multiplier must be at least 1.0');
        }

        $this->multiplier = $multiplier;

        return $this;
    }

    public function withMaxDelayMs(int $maxDelayMs): self
    {
        if ($maxDelayMs < 0) {
            throw new InvalidArgumentException('Max delay must be non-negative');
        }

        $this->maxDelayMs = $maxDelayMs;

        return $this;
    }

    public function withJitter(bool $enabled): self
    {
        $this->jitter = $enabled;

        return $this;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getDelayMs(int $attempt): int
    {
        $delay = (int) ($this->baseDelayMs * pow($this->multiplier, $attempt - 1));

        $delay = min($delay, $this->maxDelayMs);

        if ($this->jitter) {
            $jitterAmount = (int) ($delay * 0.3);
            $delay = $delay + random_int(-$jitterAmount, $jitterAmount);
        }

        return max(0, $delay);
    }

    /**
     * @return array<int>
     */
    public function getAllDelays(): array
    {
        $delays = [];

        for ($i = 1; $i <= $this->maxAttempts; $i++) {
            $delays[] = $this->getDelayMs($i);
        }

        return $delays;
    }
}

final class RetryExecutor
{
    public function __construct(
        private RetryStrategy $strategy,
    ) {}

    public static function withDefault(): self
    {
        return new self(RetryStrategy::default());
    }

    public static function withStrategy(RetryStrategy $strategy): self
    {
        return new self($strategy);
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @param callable(\Throwable)? $onFailure
     * @return T
     */
    public function execute(callable $operation, ?callable $onFailure = null): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->strategy->getMaxAttempts(); $attempt++) {
            try {
                return $operation();
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($onFailure !== null) {
                    $shouldRetry = $onFailure($e, $attempt);

                    if ($shouldRetry === false) {
                        throw $e;
                    }
                }

                if ($attempt < $this->strategy->getMaxAttempts()) {
                    $delay = $this->strategy->getDelayMs($attempt);
                    usleep($delay * 1000);
                }
            }
        }

        throw $lastException;
    }

    public function executeWithResult(
        callable $operation,
        mixed &$result = null,
    ): bool {
        $result = null;

        try {
            $result = $this->execute($operation);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
