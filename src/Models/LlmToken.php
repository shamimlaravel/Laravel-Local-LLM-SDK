<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LlmToken extends Model
{
    protected $table = 'llm_tokens';

    protected $fillable = [
        'name',
        'hashed_token',
        'abilities',
        'rate_limit',
        'monthly_quota',
        'revoked_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'rate_limit' => 'integer',
        'monthly_quota' => 'integer',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(LlmUsage::class, 'token_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    public function hasAbilities(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if (!$this->hasAbility($ability)) {
                return false;
            }
        }

        return true;
    }
}
