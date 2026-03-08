<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmUsage extends Model
{
    protected $table = 'llm_usages';

    protected $fillable = [
        'token_id',
        'driver',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'latency_ms',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'latency_ms' => 'float',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(LlmToken::class, 'token_id');
    }

    public function getTotalTokens(): int
    {
        return $this->prompt_tokens + $this->completion_tokens;
    }
}
