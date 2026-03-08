<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Security;

final class ResponseSanitizer
{
    private const MAX_CONTENT_LENGTH = 1000000;
    private const FORBIDDEN_PATTERNS = [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/javascript:/i',
        '/on\w+\s*=/i',
    ];

    public function sanitize(string $content): string
    {
        $content = mb_substr($content, 0, self::MAX_CONTENT_LENGTH);

        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            $content = preg_replace($pattern, '', $content) ?? $content;
        }

        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content) ?? $content;

        return trim($content);
    }

    public function removeControlCharacters(string $content): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/', '', $content) ?? $content;
    }

    public function truncate(string $content, int $maxLength = 10000): string
    {
        if (mb_strlen($content) <= $maxLength) {
            return $content;
        }

        return mb_substr($content, 0, $maxLength) . '...[truncated]';
    }

    public function stripHtml(string $content): string
    {
        return strip_tags($content);
    }

    public function normalizeWhitespace(string $content): string
    {
        $content = preg_replace('/\s+/', ' ', $content) ?? $content;
        return trim($content);
    }
}
