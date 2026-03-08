<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Validation;

use Illuminate\Contracts\Validation\Rule;

class LlmConfigDriverRule implements Rule
{
    private const VALID_DRIVERS = [
        'ollama',
        'lmstudio',
        'openai-compatible',
        'airllm-llama',
    ];

    public function passes($attribute, $value): bool
    {
        return in_array($value, self::VALID_DRIVERS, true);
    }

    public function message(): string
    {
        return 'The :attribute must be a valid driver: ' . implode(', ', self::VALID_DRIVERS);
    }
}

class LlmConfigUrlRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false
            || preg_match('/^https?:\/\/[^\/]+:\d+$/', $value) === 1;
    }

    public function message(): string
    {
        return 'The :attribute must be a valid URL (e.g., http://localhost:11434)';
    }
}
