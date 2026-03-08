<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Contracts;

use LaravelLocalLlm\Models\LlmToken;

interface GuardInterface
{
    public function validate(string $token): ?LlmToken;

    public function checkAbilities(LlmToken $token, array $abilities): bool;

    public function checkRateLimit(LlmToken $token): bool;

    public function checkQuota(LlmToken $token): bool;
}
