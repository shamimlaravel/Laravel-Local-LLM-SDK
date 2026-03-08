<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelLocalLlm\Builders\ChatBuilder;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\Facades\LocalLlm;

class LlmChatController
{
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1',
            'stream' => 'nullable|boolean',
        ]);

        $model = $request->input('model', config('llm.defaults.model', 'llama3.2'));
        $stream = $request->boolean('stream', false);

        if ($stream) {
            return $this->handleStream($request, $model);
        }

        $response = LocalLlm::chatWithBuilder()
            ->model($model)
            ->withUserMessage($request->input('message'))
            ->temperature((float) $request->input('temperature', 0.7))
            ->maxTokens((int) $request->input('max_tokens', 2048))
            ->send();

        return response()->json([
            'content' => $response->content,
            'model' => $response->model,
            'usage' => [
                'prompt_tokens' => $response->usage?->promptTokens,
                'completion_tokens' => $response->usage?->completionTokens,
                'total_tokens' => $response->usage?->totalTokens,
            ],
            'latency_ms' => $response->latencyMs,
            'finish_reason' => $response->finishReason,
        ]);
    }

    private function handleStream(Request $request, string $model): JsonResponse
    {
        $response = response()->stream(function () use ($request, $model) {
            LocalLlm::chatWithBuilder()
                ->model($model)
                ->withUserMessage($request->input('message'))
                ->stream(true)
                ->sendStream(function ($chunk) {
                    $data = json_encode([
                        'content' => $chunk->content,
                        'delta' => $chunk->delta,
                        'finished' => $chunk->finished,
                        'finish_reason' => $chunk->finishReason,
                    ]);

                    echo "data: {$data}\n\n";

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                });
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);

        return $response;
    }
}
