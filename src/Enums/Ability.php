<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Enums;

enum Ability: string
{
    case CHAT = 'chat';
    case STREAM = 'stream';
    case MODELS = 'models';
    case ADMIN = 'admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
