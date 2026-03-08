# Laravel Local LLM SDK Documentation - Quick Reference

## 🚀 Quick Start

### Installation
```bash
composer require laravel-local-llm/sdk
php artisan vendor:publish --provider="LaravelLocalLlm\LocalLlmServiceProvider"
php artisan migrate
```

### Basic Usage
```php
use LaravelLocalLlm\Facades\LocalLlm;

// Simple chat
$response = LocalLlm::chat('Hello, how are you?');
echo $response->content;

// Streaming
LocalLlm::stream('Tell me a story', function($chunk) {
    echo $chunk->content;
});
```

---

## 📋 Table of Contents

1. [Getting Started](#introduction)
2. [LLM Engines](#ollama)
3. [Usage Examples](#basic-usage)
4. [Advanced Features](#authentication)
5. [Reference](#configuration)

---

## 🔌 Supported LLM Engines

| Engine | Port | Best For | Memory |
|--------|------|----------|---------|
| **Ollama** | 11434 | General purpose | 2GB+ |
| **LM Studio** | 1234 | Desktop GUI | 4GB+ |
| **llama.cpp** | 8080 | CPU-only | 2GB+ |
| **LocalAI** | 8080 | OpenAI compat | 4GB+ |
| **Jan.ai** | 39281 | Privacy | 4GB+ |
| **GPT4All** | N/A | Beginners | 2GB+ |
| **AirLLMLlama** | N/A | Low memory | 700MB |

---

## 🎯 Key Features

### ✅ Multi-Driver Support
Switch between LLM engines seamlessly with automatic failover.

### 🔒 Enterprise Security
- Token-based authentication
- Rate limiting
- Request validation
- Audit logging

### ⚡ Streaming Support
Real-time Server-Sent Events (SSE) for instant responses.

### 🔄 Intelligent Failover
Automatic fallback to healthy drivers when issues occur.

### 📊 Metrics & Monitoring
Built-in Prometheus metrics and usage tracking.

### 🛠️ Tool Calling
Define custom functions for LLMs to execute.

---

## 📖 Common Patterns

### Chat with Context
```php
$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'Explain quantum computing.']
];

$response = LocalLlm::withMessages($messages)->chat();
```

### Model Selection
```php
// Specific model
$response = LocalLlm::model('llama3.2')->chat('Hello');

// Auto-select based on capability
$response = LocalLlm::ability('code-generation')->chat('Write a function');
```

### Embeddings
```php
$embedding = LocalLlm::embedding('The quick brown fox');
// Returns vector array for semantic search/RAG
```

### Batch Processing
```php
$requests = [
    ['message' => 'First question'],
    ['message' => 'Second question']
];

$responses = LocalLlm::batch($requests)->chat();
```

---

## 🔧 Configuration

### Environment Variables
```env
LLM_DEFAULT_DRIVER=ollama
LLM_OLLAMA_URL=http://localhost:11434
LLM_LMSTUDIO_URL=http://localhost:1234/v1
LLM_API_TOKEN=your-api-token
LLM_MAX_TOKENS=2048
LLM_TEMPERATURE=0.7
```

### Config File (config/llm.php)
```php
return [
    'default' => env('LLM_DEFAULT_DRIVER', 'ollama'),
    'drivers' => [
        'ollama' => [
            'url' => env('LLM_OLLAMA_URL', 'http://localhost:11434'),
            'timeout' => 120,
        ],
        // ... more drivers
    ],
];
```

---

## 🛡️ Security

### API Token Generation
```bash
php artisan llm:token:create "My Application"
```

### Middleware Protection
```php
Route::middleware(['llm.token'])->group(function () {
    Route::post('/chat', [LlmChatController::class, 'chat']);
});
```

### Rate Limiting
```php
// Default: 60 requests per minute
LLM_RATE_LIMIT=60
```

---

## 📊 Monitoring

### Health Check
```bash
php artisan llm:health
```

### List Models
```bash
php artisan llm:models
```

### Clear Cache
```bash
php artisan llm:cache:clear
```

### View Metrics
```bash
# Prometheus endpoint
GET /metrics
```

---

## 🐛 Troubleshooting

### Connection Refused
```bash
# Check if LLM engine is running
curl http://localhost:11434/api/tags

# Verify firewall settings
sudo ufw allow 11434
```

### Out of Memory
```bash
# Use AirLLMLlama for low-memory systems
LLM_DEFAULT_DRIVER=airllm
```

### Slow Responses
```bash
# Enable caching
LLM_CACHE_ENABLED=true
LLM_CACHE_TTL=3600
```

---

## 📚 Resources

### Documentation Links
- [Full Documentation](docs/index.html)
- [API Reference](#api-reference)
- [Examples](src/Examples/)
- [Contributing Guide](CONTRIBUTING.md)

### Community
- [GitHub Repository](https://github.com/laravel-local-llm/laravel-local-llm-sdk)
- [Issue Tracker](https://github.com/laravel-local-llm/laravel-local-llm-sdk/issues)
- [Discussions](https://github.com/laravel-local-llm/laravel-local-llm-sdk/discussions)

---

## 🎨 Documentation Features

### Navigation
- **Sidebar**: Collapsible sections on desktop, slide-out on mobile
- **Navigation Dots**: Quick access to major sections (desktop only)
- **Search**: Coming soon
- **Dark Mode**: Toggle in header

### Interactive Elements
- **Code Copy**: Click copy button on code blocks
- **Smooth Scroll**: Animated scrolling to sections
- **Reveal Animations**: Content fades in as you scroll
- **Mouse Follower**: Desktop-only visual effect

### Accessibility
- Keyboard navigation support
- ARIA labels on all interactive elements
- High contrast mode
- Reduced motion support

---

## 📱 Responsive Design

### Breakpoints
- Mobile: < 768px
- Tablet: 768px - 1024px
- Desktop: 1024px - 1280px
- Large Desktop: > 1280px

### Mobile Optimizations
- Touch-friendly buttons (44px minimum)
- Collapsible sidebar
- Simplified navigation
- Optimized font sizes

---

## 🔮 Advanced Features

### Custom Drivers
```php
class MyCustomDriver implements DriverInterface
{
    public function chat(ChatRequest $request): ChatResponse
    {
        // Implement your custom logic
    }
}
```

### Event Hooks
```php
Event::listen(ChatCompleted::class, function ($event) {
    // Log or process completed chat
});
```

### Webhooks
```php
// Configure in config/llm.php
'webhooks' => [
    'chat_completed' => 'https://your-app.com/webhook',
]
```

---

## 📈 Performance Tips

1. **Enable Caching**: Store frequent responses
2. **Use Streaming**: Better UX for long responses
3. **Batch Requests**: Process multiple items together
4. **Model Selection**: Choose appropriate model for task
5. **Token Limits**: Set reasonable max_tokens

---

## 🎯 Best Practices

### Code Organization
```php
// Service class approach
class AIService
{
    public function generateContent($prompt)
    {
        return LocalLlm::model('llama3.2')
            ->withTemperature(0.7)
            ->chat($prompt);
    }
}
```

### Error Handling
```php
try {
    $response = LocalLlm::chat($message);
} catch (\Exception $e) {
    // Fallback or user-friendly error
    logger()->error('LLM failed: ' . $e->getMessage());
}
```

### Testing
```php
public function test_chat_response()
{
    LocalLlm::fake();
    
    $response = LocalLlm::chat('Hello');
    
    $this->assertNotEmpty($response->content);
}
```

---

## 📞 Support

For questions or issues:
1. Check this documentation first
2. Search existing GitHub issues
3. Create a new issue with details
4. Join community discussions

---

**Last Updated:** March 9, 2026  
**Version:** 1.0.0  
**Status:** Production Ready ✅
