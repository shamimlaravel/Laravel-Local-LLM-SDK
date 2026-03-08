<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Helpers;

class TokenCalculator
{
    public static function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    public static function estimateTokensForMessages(array $messages): int
    {
        $total = 4;

        foreach ($messages as $message) {
            $total += 4;
            $total += self::estimateTokens($message['content'] ?? '');
        }

        $total += 2;

        return $total;
    }

    public static function calculateCost(int $promptTokens, int $completionTokens, float $promptPrice, float $completionPrice): float
    {
        return ($promptTokens * $promptPrice) + ($completionTokens * $completionPrice);
    }

    public static function formatTokens(int $tokens): string
    {
        if ($tokens < 1000) {
            return (string) $tokens;
        }

        return sprintf('%.1fK', $tokens / 1000);
    }
}
