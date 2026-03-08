<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Helpers;

class ModelSelector
{
    public static function selectForTask(string $task, array $availableModels): ?string
    {
        $taskMappings = [
            'code' => ['code', 'codellama', 'starcoder', 'deepseek-coder'],
            'reasoning' => ['reasoning', 'deepseek', 'qwen'],
            'chat' => ['chat', 'llama', 'mistral', 'mixtral'],
            'embedding' => ['embedding', 'text-embedding'],
            'fast' => ['tiny', '1b', '2b', 'fast'],
            'quality' => ['70b', '8x7b', 'large', 'pro'],
        ];

        $keywords = $taskMappings[$task] ?? [$task];

        foreach ($availableModels as $model) {
            $modelLower = strtolower($model);
            
            foreach ($keywords as $keyword) {
                if (str_contains($modelLower, $keyword)) {
                    return $model;
                }
            }
        }

        return $availableModels[0] ?? null;
    }

    public static function selectByHardware(array $models, array $hardware): ?string
    {
        $vram = $hardware['vram'] ?? PHP_INT_MAX;
        
        $suitable = [];
        
        foreach ($models as $model) {
            $required = self::estimateVram($model);
            
            if ($required <= $vram) {
                $suitable[$model] = $required;
            }
        }
        
        if (empty($suitable)) {
            return null;
        }
        
        asort($suitable);
        
        return array_key_first($suitable);
    }

    private static function estimateVram(string $model): int
    {
        $patterns = [
            '/(\d+)b/i' => 1,
            '/(\d+)x(\d+)b/i' => 1,
        ];

        foreach ($patterns as $pattern => $multiplier) {
            if (preg_match($pattern, $model, $matches)) {
                $size = (int) ($matches[1] ?? 1);
                
                if (isset($matches[2])) {
                    $size *= (int) $matches[2];
                }
                
                return $size * 1000000000;
            }
        }

        return 4000000000;
    }
}
