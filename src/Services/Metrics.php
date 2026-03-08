<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Services;

use LaravelLocalLlm\DTO\ChatResponse;

class Metrics
{
    private array $metrics = [];

    public function recordRequest(string $driver, string $model, float $latencyMs, int $promptTokens, int $completionTokens): void
    {
        $key = $driver . ':' . $model;

        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = [
                'driver' => $driver,
                'model' => $model,
                'requests' => 0,
                'total_latency_ms' => 0.0,
                'total_prompt_tokens' => 0,
                'total_completion_tokens' => 0,
                'errors' => 0,
            ];
        }

        $this->metrics[$key]['requests']++;
        $this->metrics[$key]['total_latency_ms'] += $latencyMs;
        $this->metrics[$key]['total_prompt_tokens'] += $promptTokens;
        $this->metrics[$key]['total_completion_tokens'] += $completionTokens;
    }

    public function recordError(string $driver, string $model): void
    {
        $key = $driver . ':' . $model;

        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = [
                'driver' => $driver,
                'model' => $model,
                'requests' => 0,
                'total_latency_ms' => 0.0,
                'total_prompt_tokens' => 0,
                'total_completion_tokens' => 0,
                'errors' => 0,
            ];
        }

        $this->metrics[$key]['errors']++;
    }

    public function getMetrics(): array
    {
        $results = [];

        foreach ($this->metrics as $key => $metric) {
            $results[$key] = [
                'driver' => $metric['driver'],
                'model' => $metric['model'],
                'requests' => $metric['requests'],
                'errors' => $metric['errors'],
                'success_rate' => $metric['requests'] > 0 
                    ? (($metric['requests'] - $metric['errors']) / $metric['requests']) * 100 
                    : 0,
                'avg_latency_ms' => $metric['requests'] > 0 
                    ? $metric['total_latency_ms'] / $metric['requests'] 
                    : 0,
                'total_prompt_tokens' => $metric['total_prompt_tokens'],
                'total_completion_tokens' => $metric['total_completion_tokens'],
                'total_tokens' => $metric['total_prompt_tokens'] + $metric['total_completion_tokens'],
            ];
        }

        return $results;
    }

    public function getAggregateMetrics(): array
    {
        $totalRequests = 0;
        $totalErrors = 0;
        $totalLatency = 0.0;
        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;

        foreach ($this->metrics as $metric) {
            $totalRequests += $metric['requests'];
            $totalErrors += $metric['errors'];
            $totalLatency += $metric['total_latency_ms'];
            $totalPromptTokens += $metric['total_prompt_tokens'];
            $totalCompletionTokens += $metric['total_completion_tokens'];
        }

        return [
            'total_requests' => $totalRequests,
            'total_errors' => $totalErrors,
            'success_rate' => $totalRequests > 0 
                ? (($totalRequests - $totalErrors) / $totalRequests) * 100 
                : 0,
            'avg_latency_ms' => $totalRequests > 0 ? $totalLatency / $totalRequests : 0,
            'total_tokens' => $totalPromptTokens + $totalCompletionTokens,
            'total_prompt_tokens' => $totalPromptTokens,
            'total_completion_tokens' => $totalCompletionTokens,
        ];
    }

    public function reset(): void
    {
        $this->metrics = [];
    }

    public function toPrometheusFormat(): string
    {
        $output = '';
        $aggregate = $this->getAggregateMetrics();

        $output .= "# HELP llm_requests_total Total number of LLM requests\n";
        $output .= "# TYPE llm_requests_total counter\n";
        $output .= sprintf("llm_requests_total %d\n\n", $aggregate['total_requests']);

        $output .= "# HELP llm_errors_total Total number of LLM errors\n";
        $output .= "# TYPE llm_errors_total counter\n";
        $output .= sprintf("llm_errors_total %d\n\n", $aggregate['total_errors']);

        $output .= "# HELP llm_latency_ms Average latency in milliseconds\n";
        $output .= "# TYPE llm_latency_ms gauge\n";
        $output .= sprintf("llm_latency_ms %.2f\n\n", $aggregate['avg_latency_ms']);

        $output .= "# HELP llm_tokens_total Total tokens used\n";
        $output .= "# TYPE llm_tokens_total counter\n";
        $output .= sprintf("llm_tokens_total %d\n\n", $aggregate['total_tokens']);

        return $output;
    }
}
