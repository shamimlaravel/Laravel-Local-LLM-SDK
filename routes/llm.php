<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelLocalLlm\Http\Controllers\LlmChatController;
use LaravelLocalLlm\Middleware\LlmTokenGuard;

Route::prefix('llm')->middleware(['api', LlmTokenGuard::class . ':chat,stream'])->group(function () {
    Route::post('/chat', [LlmChatController::class, 'chat']);
});

Route::prefix('llm')->middleware(['api'])->group(function () {
    Route::get('/models', function () {
        $models = \LaravelLocalLlm\Facades\LocalLlm::models();
        
        return response()->json([
            'models' => array_map(fn ($model) => [
                'name' => $model->name,
                'id' => $model->id,
                'modified_at' => $model->modifiedAt?->toIso8601String(),
            ], $models),
        ]);
    });

    Route::get('/health', function () {
        $health = \LaravelLocalLlm\Facades\LocalLlm::health();
        
        return response()->json([
            'healthy' => $health,
            'driver' => config('llm.defaults.driver'),
        ]);
    });
});
