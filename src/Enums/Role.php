<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Enums;

enum Role: string
{
    case SYSTEM = 'system';
    case USER = 'user';
    case ASSISTANT = 'assistant';
    case TOOL = 'tool';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
