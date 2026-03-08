<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Guards;

use LaravelLocalLlm\Contracts\GuardInterface;
use LaravelLocalLlm\Models\LlmToken;
use LaravelLocalLlm\Models\LlmUsage;

class TokenGuard implements GuardInterface
{
    public function validate(string $token): ?LlmToken
    {
        $token = LlmToken::where('revoked_at', null)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get()
            ->first(fn (LlmToken $t) => hash_equals($t->hashed_token, $token));

        return $token;
    }

    public function checkAbilities(LlmToken $token, array $abilities): bool
    {
        return $token->hasAbilities($abilities);
    }

    public function checkRateLimit(LlmToken $token): bool
    {
        $window = (int) config('llm.rate_limit.window', 60);
        $rateLimit = $token->rate_limit ?? config('llm.rate_limit.default', 60);

        $count = LlmUsage::where('token_id', $token->id)
            ->where('created_at', '>=', now()->subSeconds($window))
            ->count();

        return $count < $rateLimit;
    }

    public function checkQuota(LlmToken $token): bool
    {
        $quota = $token->monthly_quota ?? config('llm.quota.default', 1000000);

        $used = LlmUsage::where('token_id', $token->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('prompt_tokens + completion_tokens');

        return $used < $quota;
    }
}
