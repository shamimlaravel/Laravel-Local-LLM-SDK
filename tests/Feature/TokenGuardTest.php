<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use LaravelLocalLlm\Models\LlmToken;
use LaravelLocalLlm\Guards\TokenGuard;
use LaravelLocalLlm\Contracts\GuardInterface;

class TokenGuardTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\LaravelLocalLlm\LocalLlmServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('llm.rate_limit.default', 60);
        $app['config']->set('llm.rate_limit.window', 60);
        $app['config']->set('llm.quota.default', 1000000);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_validate_returns_token_when_valid(): void
    {
        $token = LlmToken::create([
            'name' => 'Test Token',
            'hashed_token' => hash('sha256', 'test-token-123'),
            'abilities' => ['chat', 'stream'],
            'rate_limit' => 60,
            'monthly_quota' => 1000000,
        ]);

        $guard = new TokenGuard();
        
        $result = $guard->validate('test-token-123');

        $this->assertNotNull($result);
        $this->assertEquals($token->id, $result->id);
    }

    public function test_validate_returns_null_for_invalid_token(): void
    {
        $guard = new TokenGuard();
        
        $result = $guard->validate('invalid-token');

        $this->assertNull($result);
    }

    public function test_validate_returns_null_for_revoked_token(): void
    {
        LlmToken::create([
            'name' => 'Revoked Token',
            'hashed_token' => hash('sha256', 'revoked-token'),
            'abilities' => ['chat'],
            'revoked_at' => now(),
        ]);

        $guard = new TokenGuard();
        
        $result = $guard->validate('revoked-token');

        $this->assertNull($result);
    }

    public function test_validate_returns_null_for_expired_token(): void
    {
        LlmToken::create([
            'name' => 'Expired Token',
            'hashed_token' => hash('sha256', 'expired-token'),
            'abilities' => ['chat'],
            'expires_at' => now()->subDay(),
        ]);

        $guard = new TokenGuard();
        
        $result = $guard->validate('expired-token');

        $this->assertNull($result);
    }

    public function test_check_abilities_returns_true_when_has_abilities(): void
    {
        $token = new LlmToken();
        $token->abilities = ['chat', 'stream', 'models'];

        $guard = new TokenGuard();
        
        $result = $guard->checkAbilities($token, ['chat', 'stream']);

        $this->assertTrue($result);
    }

    public function test_check_abilities_returns_false_when_missing_abilities(): void
    {
        $token = new LlmToken();
        $token->abilities = ['chat'];

        $guard = new TokenGuard();
        
        $result = $guard->checkAbilities($token, ['chat', 'admin']);

        $this->assertFalse($result);
    }

    public function test_check_abilities_returns_true_for_empty_abilities(): void
    {
        $token = new LlmToken();
        $token->abilities = [];

        $guard = new TokenGuard();
        
        $result = $guard->checkAbilities($token, []);

        $this->assertTrue($result);
    }

    public function test_token_has_ability(): void
    {
        $token = new LlmToken();
        $token->abilities = ['chat', 'stream'];

        $this->assertTrue($token->hasAbility('chat'));
        $this->assertFalse($token->hasAbility('admin'));
    }

    public function test_token_has_abilities(): void
    {
        $token = new LlmToken();
        $token->abilities = ['chat', 'stream', 'models'];

        $this->assertTrue($token->hasAbilities(['chat', 'stream']));
        $this->assertFalse($token->hasAbilities(['chat', 'admin']));
    }

    public function test_token_is_revoked(): void
    {
        $token = new LlmToken();
        
        $this->assertFalse($token->isRevoked());

        $token->revoked_at = now();
        
        $this->assertTrue($token->isRevoked());
    }

    public function test_token_is_expired(): void
    {
        $token = new LlmToken();
        
        $this->assertFalse($token->isExpired());

        $token->expires_at = now()->subDay();
        
        $this->assertTrue($token->isExpired());
    }

    public function test_token_is_not_expired_when_null(): void
    {
        $token = new LlmToken();
        
        $this->assertFalse($token->isExpired());
    }
}
