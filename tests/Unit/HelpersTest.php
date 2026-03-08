<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelLocalLlm\Helpers\TokenCalculator;
use LaravelLocalLlm\Helpers\ResponseFormatter;

class HelpersTest extends TestCase
{
    public function test_token_calculator_estimate(): void
    {
        $text = 'Hello world';
        $tokens = TokenCalculator::estimateTokens($text);
        
        $this->assertEquals(3, $tokens);
    }

    public function test_token_calculator_estimate_long_text(): void
    {
        $text = str_repeat('Hello world ', 100);
        $tokens = TokenCalculator::estimateTokens($text);
        
        $this->assertEquals(300, $tokens);
    }

    public function test_token_calculator_estimate_messages(): void
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are helpful'],
            ['role' => 'user', 'content' => 'Hello'],
        ];
        
        $tokens = TokenCalculator::estimateTokensForMessages($messages);
        
        $this->assertGreaterThan(0, $tokens);
    }

    public function test_token_calculator_cost(): void
    {
        $cost = TokenCalculator::calculateCost(100, 50, 0.001, 0.002);
        
        $this->assertEquals(0.2, $cost);
    }

    public function test_token_calculator_format_tokens(): void
    {
        $this->assertEquals('500', TokenCalculator::formatTokens(500));
        $this->assertEquals('1.5K', TokenCalculator::formatTokens(1500));
        $this->assertEquals('10.0K', TokenCalculator::formatTokens(10000));
    }

    public function test_response_formatter_markdown(): void
    {
        $text = '**bold** and *italic* and `code`';
        $html = ResponseFormatter::markdown($text);
        
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
        $this->assertStringContainsString('<code>code</code>', $html);
    }

    public function test_response_formatter_json_valid(): void
    {
        $json = '{"key": "value"}';
        $result = ResponseFormatter::json($json);
        
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
    }

    public function test_response_formatter_json_invalid(): void
    {
        $text = 'not json';
        $result = ResponseFormatter::json($text);
        
        $this->assertNull($result);
    }

    public function test_response_formatter_truncate(): void
    {
        $text = 'This is a very long text that should be truncated';
        $truncated = ResponseFormatter::truncate($text, 20);
        
        $this->assertEquals('This is a very lo...', $truncated);
        $this->assertEquals(20, strlen($truncated));
    }

    public function test_response_formatter_truncate_short(): void
    {
        $text = 'Short';
        $truncated = ResponseFormatter::truncate($text, 20);
        
        $this->assertEquals('Short', $truncated);
    }

    public function test_response_formatter_extract_code(): void
    {
        $text = 'Here is some code:
```php
echo "Hello";
```
And more text';

        $codeBlocks = ResponseFormatter::extractCode($text);
        
        $this->assertCount(1, $codeBlocks);
        $this->assertEquals('php', $codeBlocks[0]['language']);
        $this->assertEquals('echo "Hello";', $codeBlocks[0]['code']);
    }
}
