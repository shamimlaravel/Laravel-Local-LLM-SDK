<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Security;

use InvalidArgumentException;

final class RequestValidator
{
    private const MAX_MODEL_NAME_LENGTH = 256;
    private const MAX_MESSAGE_LENGTH = 100000;
    private const MAX_MESSAGES = 100;
    private const MAX_TEMPERATURE = 2.0;
    private const MIN_TEMPERATURE = 0.0;
    private const MAX_MAX_TOKENS = 100000;

    public function validateModel(string $model): bool
    {
        if (empty($model)) {
            return false;
        }

        if (strlen($model) > self::MAX_MODEL_NAME_LENGTH) {
            return false;
        }

        if (!preg_match('/^[\w\-\.]+$/', $model)) {
            return false;
        }

        return true;
    }

    public function validateTemperature(float $temperature): bool
    {
        return $temperature >= self::MIN_TEMPERATURE && $temperature <= self::MAX_TEMPERATURE;
    }

    public function validateMaxTokens(?int $maxTokens): bool
    {
        if ($maxTokens === null) {
            return true;
        }

        return $maxTokens > 0 && $maxTokens <= self::MAX_MAX_TOKENS;
    }

    public function validateMessages(array $messages): bool
    {
        if (empty($messages)) {
            return false;
        }

        if (count($messages) > self::MAX_MESSAGES) {
            return false;
        }

        foreach ($messages as $message) {
            if (!isset($message['role']) || !isset($message['content'])) {
                return false;
            }

            if (strlen($message['content']) > self::MAX_MESSAGE_LENGTH) {
                return false;
            }

            $validRoles = ['system', 'user', 'assistant', 'function'];
            if (!in_array($message['role'], $validRoles, true)) {
                return false;
            }
        }

        return true;
    }

    public function validateStopSequence(string|array|null $stop): bool
    {
        if ($stop === null) {
            return true;
        }

        if (is_string($stop)) {
            return strlen($stop) <= 1000;
        }

        if (is_array($stop)) {
            foreach ($stop as $s) {
                if (!is_string($s) || strlen($s) > 1000) {
                    return false;
                }
            }
            return count($stop) <= 4;
        }

        return false;
    }

    public function sanitizeModel(string $model): string
    {
        return preg_replace('/[^\w\-\.]/', '', $model) ?? $model;
    }

    public function sanitizeContent(string $content): string
    {
        $content = preg_replace('/[\x00-\x1F\x7F]/', '', $content) ?? $content;
        
        return trim($content);
    }
}
