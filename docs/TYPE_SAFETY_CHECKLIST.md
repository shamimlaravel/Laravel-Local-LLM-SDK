# Type Safety Checklist for Contributors

This document outlines the type safety requirements for contributing to Laravel Local LLM SDK.

## PHP 8.4 Strict Typing Requirements

### 1. Declare Strict Types
```php
<?php

declare(strict_types=1);
```
- MUST be the first line in every PHP file
- MUST have no whitespace before `<?php`

### 2. Return Types
All functions and methods MUST have explicit return types:
```php
// Good
public function getDriver(): Driver
{
    return Driver::OLLAMA;
}

// Bad
public function getDriver()
{
    return Driver::OLLAMA;
}
```

### 3. Parameter Types
All function and method parameters MUST have explicit types:
```php
// Good
public function chat(ChatRequest $request): ChatResponse
{
    // ...
}

// Bad
public function chat($request)
{
    // ...
}
```

### 4. Property Types
All class properties MUST have explicit types:
```php
// Good
readonly class ChatResponse
{
    public function __construct(
        public string $content,
        public int $promptTokens,
    ) {}
}

// Bad
class ChatResponse
{
    public $content;
}
```

### 5. Typed Properties
- Use `readonly` for immutable properties
- Use `public` with `readonly` for DTOs
- Avoid mutable public properties

### 6. Nullable Types
Use nullable types explicitly:
```php
// Good
public function find(?int $id): ?Model
{
    // ...
}

// Bad
public function find($id)
{
    // ...
}
```

### 7. Union Types (PHP 8.0+)
When a value can be multiple types:
```php
// Good
public function process(string|int $value): string
{
    // ...
}
```

### 8. Mixed Type
Avoid `mixed` when possible. If necessary, use with caution:
```php
// Acceptable
public function handle(mixed $data): void
{
    // ...
}

// Avoid if possible
public function process(mixed $data): mixed
```

### 9. Never Type (PHP 8.1+)
Use `never` for methods that don't return:
```php
public function throwError(string $message): never
{
    throw new \RuntimeException($message);
}
```

### 10. Void Return
Use `void` when method doesn't return a value:
```php
public function save(): void
{
    // save logic
}
```

## Type Inference Guidelines

### 1. Arrow Functions
Arrow functions automatically capture variables - use explicit return types for complex returns:
```php
// Good
$mapper = fn (Message $msg): array => $msg->toArray();
```

### 2. Array Shapes
Use array shapes for structured arrays:
```php
// Good
/** @param array{name: string, age: int} $data */
public function process(array $data): void
{
    // ...
}

// Better - Use DTO instead
public function process(PersonData $data): void
{
    // ...
}
```

### 3. Generics (PHP 8.2+)
Use generics for collection-like classes:
```php
/** @template T */
class Collection
{
    /** @var array<T> */
    private array $items;
    
    /** @return T */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }
}
```

## Laravel-Specific Guidelines

### 1. Facades
Use type hinting for facades:
```php
// Good
public function handle(Request $request): Response
{
    $service = app(LocalLlmService::class);
    // ...
}

// Also good - use instance
public function __construct(
    protected readonly LocalLlmService $llm,
) {}
```

### 2. Config
Always type config access:
```php
// Good
$timeout = (int) config('llm.timeout', 30);

// Good with nullable
$optional = config('llm.optional');
```

### 3. Models
Use proper types in Eloquent models:
```php
class LlmToken extends Model
{
    protected $casts = [
        'abilities' => 'array',
        'rate_limit' => 'integer',
    ];
    
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
```

## Static Analysis Tools

### PHPStan
- Level must be `max` (Level 9)
- All errors must be fixed before PR
- Use `@phpstan-var` for complex type hints

### Pint
- Run `composer style` before committing
- Follow PSR-12 with Laravel conventions

## Common Type Errors to Avoid

### 1. String Concatenation
```php
// Good - use interpolation
$message = "Hello {$name}";

// Also good - use sprintf
$message = sprintf('Hello %s', $name);
```

### 2. Array Access
```php
// Good
$first = $items[0] ?? null;

// Bad - may return false
$first = $items[0];
```

### 3. Type Coercion
```php
// Good
$count = (int) $value;

// Bad - implicit coercion
$count = $value;
```

## Testing Types

### 1. PHPUnit/Pest
Use proper assertions:
```php
// Good
$this->assertInstanceOf(ChatResponse::class, $response);
$this->assertSame(200, $status);
$this->assertTrue($response->isOk());

// Avoid
$this->assertEquals(200, $status); // loose comparison
```

## Code Review Checklist

Before submitting a PR, verify:

- [ ] `declare(strict_types=1)` is present
- [ ] All methods have return types
- [ ] All parameters have type hints
- [ ] All properties have types
- [ ] No `mixed` types (unless absolutely necessary)
- [ ] No `var_dump`, `dd`, `print_r` in code
- [ ] PHPStan passes at level max
- [ ] Pint shows no errors
- [ ] Tests pass
- [ ] PHPDoc matches actual types
