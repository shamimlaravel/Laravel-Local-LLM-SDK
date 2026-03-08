<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LlmTokenGuard
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'No token provided',
            ], 401);
        }

        $guard = app(\LaravelLocalLlm\Contracts\GuardInterface::class);

        $llmToken = $guard->validate($token);

        if (!$llmToken) {
            return response()->json([
                'error' => 'Invalid token',
            ], 401);
        }

        if ($llmToken->isRevoked() || $llmToken->isExpired()) {
            return response()->json([
                'error' => 'Token revoked or expired',
            ], 401);
        }

        if (!empty($abilities) && !$guard->checkAbilities($llmToken, $abilities)) {
            return response()->json([
                'error' => 'Insufficient permissions',
            ], 403);
        }

        if (!$guard->checkRateLimit($llmToken)) {
            return response()->json([
                'error' => 'Rate limit exceeded',
            ], 429);
        }

        if (!$guard->checkQuota($llmToken)) {
            return response()->json([
                'error' => 'Monthly quota exceeded',
            ], 429);
        }

        $request->attributes->set('llm_token', $llmToken);

        return $next($request);
    }
}
