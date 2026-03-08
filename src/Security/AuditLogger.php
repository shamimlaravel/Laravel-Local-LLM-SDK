<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Security;

use Illuminate\Support\Facades\Log;

final class AuditLogger
{
    public function logRequest(
        int $tokenId,
        string $action,
        string $driver,
        string $model,
        array $metadata = [],
    ): void {
        Log::channel('llm')->info('LLM Request', [
            'token_id' => $tokenId,
            'action' => $action,
            'driver' => $driver,
            'model' => $model,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function logAuthentication(int $tokenId, string $status, ?string $reason = null): void
    {
        Log::channel('llm')->info('LLM Authentication', [
            'token_id' => $tokenId,
            'status' => $status,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function logRateLimit(int $tokenId, string $limitType): void
    {
        Log::channel('llm')->warning('LLM Rate Limit Exceeded', [
            'token_id' => $tokenId,
            'limit_type' => $limitType,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function logError(
        int $tokenId,
        string $errorType,
        string $message,
        array $context = [],
    ): void {
        Log::channel('llm')->error('LLM Error', [
            'token_id' => $tokenId,
            'error_type' => $errorType,
            'message' => $message,
            'context' => $context,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function logDriverSwitch(int $tokenId, string $fromDriver, string $toDriver): void
    {
        Log::channel('llm')->info('LLM Driver Switch', [
            'token_id' => $tokenId,
            'from_driver' => $fromDriver,
            'to_driver' => $toDriver,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
