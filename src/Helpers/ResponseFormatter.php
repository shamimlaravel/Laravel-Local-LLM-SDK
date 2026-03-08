<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Helpers;

class ResponseFormatter
{
    public static function markdown(string $content): string
    {
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
        $content = preg_replace('/`(.+?)`/', '<code>$1</code>', $content);
        $content = preg_replace('/```(\w+)?\n(.+?)```/s', '<pre><code class="language-$1">$2</code></pre>', $content);

        return nl2br($content);
    }

    public static function json(string $content): ?array
    {
        $decoded = json_decode($content, true);
        
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    public static function truncate(string $content, int $maxLength = 100, string $suffix = '...'): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        return substr($content, 0, $maxLength - strlen($suffix)) . $suffix;
    }

    public static function extractCode(string $content): array
    {
        preg_match_all('/```(\w+)?\n(.+?)```/s', $content, $matches);
        
        $codeBlocks = [];
        
        foreach ($matches[2] ?? [] as $index => $code) {
            $codeBlocks[] = [
                'language' => $matches[1][$index] ?? 'text',
                'code' => trim($code),
            ];
        }

        return $codeBlocks;
    }
}
