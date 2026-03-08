<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Examples;

use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\Facades\LocalLlm;
use LaravelLocalLlm\Tools\Tool;
use LaravelLocalLlm\Tools\ToolParameters;

class ToolCallingService
{
    public function getWeather(string $location): string
    {
        return json_encode([
            'location' => $location,
            'temperature' => 72,
            'condition' => 'sunny',
            'humidity' => 45,
        ]);
    }

    public function getCurrentTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function search(string $query): string
    {
        return json_encode([
            'query' => $query,
            'results' => [
                'result_1' => 'Sample result for: ' . $query,
                'result_2' => 'Another relevant result',
            ],
        ]);
    }

    public function runWithTools(string $userMessage): string
    {
        $weatherTool = Tool::create(
            name: 'get_weather',
            description: 'Get current weather for a location',
            parameters: new ToolParameters(
                type: 'object',
                properties: [
                    'location' => [
                        'type' => 'string',
                        'description' => 'City name',
                    ],
                ],
                required: ['location']
            )
        );

        $timeTool = Tool::create(
            name: 'get_current_time',
            description: 'Get the current date and time',
            parameters: new ToolParameters(
                type: 'object',
                properties: [],
                required: []
            )
        );

        $searchTool = Tool::create(
            name: 'search',
            description: 'Search for information',
            parameters: new ToolParameters(
                type: 'object',
                properties: [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Search query',
                    ],
                ],
                required: ['query']
            )
        );

        $response = LocalLlm::chatWithBuilder()
            ->model(config('llm.defaults.model', 'llama3.2'))
            ->withUserMessage($userMessage)
            ->withTools([$weatherTool, $timeTool, $searchTool])
            ->send();

        if ($response->toolCalls !== null && count($response->toolCalls) > 0) {
            $messages = $response->messages ?? [];
            $messages[] = Message::assistant('', toolCalls: $response->toolCalls);

            foreach ($response->toolCalls as $toolCall) {
                $result = match ($toolCall->function->name) {
                    'get_weather' => $this->getWeather($toolCall->function->arguments['location'] ?? ''),
                    'get_current_time' => $this->getCurrentTime(),
                    'search' => $this->search($toolCall->function->arguments['query'] ?? ''),
                    default => 'Unknown tool',
                };

                $messages[] = Message::tool($toolCall->id, $result);
            }

            $finalResponse = LocalLlm::chatWithBuilder()
                ->model(config('llm.defaults.model', 'llama3.2'))
                ->withMessages($messages)
                ->send();

            return $finalResponse->content;
        }

        return $response->content;
    }
}
