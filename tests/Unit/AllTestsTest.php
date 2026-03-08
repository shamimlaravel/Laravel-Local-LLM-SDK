<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelLocalLlm\Enums\Driver;
use LaravelLocalLlm\Enums\Role;
use LaravelLocalLlm\Enums\Ability;
use LaravelLocalLlm\DTO\Message;
use LaravelLocalLlm\DTO\ChatRequest;
use LaravelLocalLlm\DTO\ChatResponse;
use LaravelLocalLlm\DTO\StreamChunk;
use LaravelLocalLlm\DTO\ModelInfo;
use LaravelLocalLlm\Exceptions\NoAvailableDriverException;
use LaravelLocalLlm\Retry\RetryStrategy;
use LaravelLocalLlm\Retry\RetryExecutor;
use LaravelLocalLlm\Templates\PromptTemplate;
use LaravelLocalLlm\Tools\ToolParameter;
use LaravelLocalLlm\Tools\StringParameter;
use LaravelLocalLlm\Tools\IntegerParameter;
use LaravelLocalLlm\Tools\BooleanParameter;
use LaravelLocalLlm\Tools\EnumParameter;
use LaravelLocalLlm\Tools\ArrayParameter;
use LaravelLocalLlm\Tools\ObjectParameter;
use LaravelLocalLlm\Tools\Tool;
use LaravelLocalLlm\Tools\CallableTool;
use LaravelLocalLlm\Tools\ToolCall;
use LaravelLocalLlm\Tools\ToolResult;

class MessageTest extends TestCase
{
    public function test_create_system_message(): void
    {
        $message = Message::system('You are helpful');
        
        self::assertEquals(Role::SYSTEM, $message->role);
        self::assertEquals('You are helpful', $message->content);
    }

    public function test_create_user_message(): void
    {
        $message = Message::user('Hello');
        
        self::assertEquals(Role::USER, $message->role);
        self::assertEquals('Hello', $message->content);
    }

    public function test_create_assistant_message(): void
    {
        $message = Message::assistant('Hi there');
        
        self::assertEquals(Role::ASSISTANT, $message->role);
        self::assertEquals('Hi there', $message->content);
    }

    public function test_message_to_array(): void
    {
        $message = Message::user('Hello');
        $array = $message->toArray();
        
        self::assertEquals(['role' => 'user', 'content' => 'Hello'], $array);
    }

    public function test_message_constructor(): void
    {
        $message = new Message(Role::SYSTEM, 'System prompt');
        
        self::assertEquals(Role::SYSTEM, $message->role);
        self::assertEquals('System prompt', $message->content);
    }
}

class ChatRequestTest extends TestCase
{
    public function test_create_chat_request(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            temperature: 0.7,
        );
        
        self::assertEquals('llama3.2', $request->model);
        self::assertEquals(0.7, $request->temperature);
        self::assertFalse($request->stream);
        self::assertNull($request->maxTokens);
        self::assertNull($request->stop);
    }

    public function test_chat_request_to_array(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            temperature: 0.5,
        );
        
        $array = $request->toArray();
        
        self::assertEquals('llama3.2', $array['model']);
        self::assertEquals(0.5, $array['temperature']);
        self::assertFalse($array['stream']);
    }

    public function test_chat_request_with_max_tokens(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            maxTokens: 100,
        );
        
        $array = $request->toArray();
        
        self::assertEquals(100, $array['max_tokens']);
    }

    public function test_chat_request_with_stop(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            stop: '\n',
        );
        
        $array = $request->toArray();
        
        self::assertEquals(['\n'], $array['stop']);
    }

    public function test_chat_request_streaming(): void
    {
        $request = new ChatRequest(
            model: 'llama3.2',
            messages: [Message::user('Hello')],
            stream: true,
        );
        
        self::assertTrue($request->stream);
    }
}

class ChatResponseTest extends TestCase
{
    public function test_create_chat_response(): void
    {
        $response = new ChatResponse(
            content: 'Hello there',
            promptTokens: 10,
            completionTokens: 5,
            latencyMs: 100.5,
            driver: Driver::OLLAMA,
            model: 'llama3.2',
        );
        
        self::assertEquals('Hello there', $response->content);
        self::assertEquals(10, $response->promptTokens);
        self::assertEquals(5, $response->completionTokens);
        self::assertEquals(100.5, $response->latencyMs);
        self::assertEquals(Driver::OLLAMA, $response->driver);
        self::assertEquals('llama3.2', $response->model);
    }

    public function test_total_tokens(): void
    {
        $response = new ChatResponse(
            content: 'Hello',
            promptTokens: 10,
            completionTokens: 5,
            latencyMs: 100.0,
            driver: Driver::OLLAMA,
            model: 'llama3.2',
        );
        
        self::assertEquals(15, $response->totalTokens());
    }

    public function test_to_array(): void
    {
        $response = new ChatResponse(
            content: 'Hello',
            promptTokens: 10,
            completionTokens: 5,
            latencyMs: 100.0,
            driver: Driver::OLLAMA,
            model: 'llama3.2',
        );
        
        $array = $response->toArray();
        
        self::assertArrayHasKey('content', $array);
        self::assertArrayHasKey('prompt_tokens', $array);
        self::assertArrayHasKey('completion_tokens', $array);
        self::assertArrayHasKey('total_token', $array);
        self::assertArrayHasKey('latency_ms', $array);
        self::assertArrayHasKey('driver', $array);
        self::assertArrayHasKey('model', $array);
    }
}

class StreamChunkTest extends TestCase
{
    public function test_create_stream_chunk(): void
    {
        $chunk = new StreamChunk(
            content: 'Hello',
            finished: false,
        );
        
        self::assertEquals('Hello', $chunk->content);
        self::assertFalse($chunk->finished);
    }

    public function test_create_finished_chunk(): void
    {
        $chunk = new StreamChunk(
            content: '',
            finished: true,
            finishReason: 'stop',
            model: 'llama3.2',
            promptTokens: 10,
            completionTokens: 5,
        );
        
        self::assertTrue($chunk->finished);
        self::assertEquals('stop', $chunk->finishReason);
        self::assertEquals('llama3.2', $chunk->model);
        self::assertEquals(10, $chunk->promptTokens);
        self::assertEquals(5, $chunk->completionTokens);
    }

    public function test_to_array(): void
    {
        $chunk = new StreamChunk(
            content: 'Test',
            finished: false,
        );
        
        $array = $chunk->toArray();
        
        self::assertEquals('Test', $array['content']);
        self::assertFalse($array['finished']);
    }
}

class ModelInfoTest extends TestCase
{
    public function test_create_model_info(): void
    {
        $model = new ModelInfo(
            id: 'llama3.2',
            name: 'Llama 3.2',
            modifiedAt: '2024-01-01',
            size: 4000000000,
        );
        
        self::assertEquals('llama3.2', $model->id);
        self::assertEquals('Llama 3.2', $model->name);
        self::assertEquals('2024-01-01', $model->modifiedAt);
        self::assertEquals(4000000000, $model->size);
    }

    public function test_from_array(): void
    {
        $model = ModelInfo::fromArray([
            'id' => 'llama3.2',
            'name' => 'Llama 3.2',
        ]);
        
        self::assertEquals('llama3.2', $model->id);
        self::assertEquals('Llama 3.2', $model->name);
    }

    public function test_to_array(): void
    {
        $model = new ModelInfo(
            id: 'llama3.2',
            name: 'Llama 3.2',
        );
        
        $array = $model->toArray();
        
        self::assertEquals('llama3.2', $array['id']);
        self::assertEquals('Llama 3.2', $array['name']);
    }
}

class RetryStrategyTest extends TestCase
{
    public function test_default_strategy(): void
    {
        $strategy = RetryStrategy::default();
        
        self::assertEquals(3, $strategy->getMaxAttempts());
    }

    public function test_exponential_strategy(): void
    {
        $strategy = RetryStrategy::exponential();
        
        self::assertGreaterThan(1.0, $strategy->getDelayMs(1));
    }

    public function test_get_delay_increases(): void
    {
        $strategy = RetryStrategy::exponential();
        
        $delay1 = $strategy->getDelayMs(1);
        $delay2 = $strategy->getDelayMs(2);
        $delay3 = $strategy->getDelayMs(3);
        
        self::assertLessThanOrEqual($delay2, $delay1);
        self::assertLessThanOrEqual($delay3, $delay2);
    }

    public function test_get_all_delays(): void
    {
        $strategy = new RetryStrategy();
        $strategy->withMaxAttempts(3);
        $strategy->withBaseDelayMs(100);
        
        $delays = $strategy->getAllDelays();
        
        self::assertCount(3, $delays);
    }

    public function test_with_max_attempts(): void
    {
        $strategy = RetryStrategy::default()->withMaxAttempts(5);
        
        self::assertEquals(5, $strategy->getMaxAttempts());
    }

    public function test_invalid_max_attempts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        RetryStrategy::default()->withMaxAttempts(0);
    }

    public function test_with_base_delay(): void
    {
        $strategy = RetryStrategy::default()->withBaseDelayMs(200);
        
        self::assertGreaterThan(0, $strategy->getDelayMs(1));
    }
}

class RetryExecutorTest extends TestCase
{
    public function test_successful_execution(): void
    {
        $executor = RetryExecutor::withDefault();
        
        $result = $executor->execute(function () {
            return 'success';
        });
        
        self::assertEquals('success', $result);
    }

    public function test_retry_on_failure(): void
    {
        $executor = RetryExecutor::withDefault();
        $attempt = 0;
        
        $result = $executor->execute(function () use (&$attempt) {
            $attempt++;
            if ($attempt < 2) {
                throw new \RuntimeException('Failed');
            }
            return 'success';
        });
        
        self::assertEquals('success', $result);
        self::assertEquals(2, $attempt);
    }

    public function test_throws_after_max_attempts(): void
    {
        $executor = RetryExecutor::withDefault();
        
        $this->expectException(\RuntimeException::class);
        
        $executor->execute(function () {
            throw new \RuntimeException('Always fails');
        });
    }

    public function test_execute_with_result(): void
    {
        $executor = RetryExecutor::withDefault();
        
        $result = null;
        $success = $executor->executeWithResult(function () {
            return 'success';
        }, $result);
        
        self::assertTrue($success);
        self::assertEquals('success', $result);
    }
}

class PromptTemplateTest extends TestCase
{
    public function test_simple_template(): void
    {
        $template = new PromptTemplate('Hello {{ name }}!');
        $template->with('name', 'World');
        
        self::assertEquals('Hello World!', $template->render());
    }

    public function test_template_with_variables(): void
    {
        $template = PromptTemplate::make('{{ greeting }} {{ name }}');
        $template->withVariables([
            'greeting' => 'Hello',
            'name' => 'World',
        ]);
        
        self::assertEquals('Hello World', $template->render());
    }

    public function test_unset_variables_removed(): void
    {
        $template = new PromptTemplate('Hello {{ name }} and {{ friend }}!');
        $template->with('name', 'World');
        
        $result = $template->render();
        
        self::assertStringNotContainsString('{{', $result);
    }

    public function test_system_prompt_template(): void
    {
        $template = PromptTemplate::systemPrompt('a helpful assistant');
        
        $result = $template->render();
        
        self::assertStringContainsString('helpful assistant', $result);
    }

    public function test_qa_prompt_template(): void
    {
        $template = PromptTemplate::qaPrompt()
            ->with('context', 'The sky is blue')
            ->with('question', 'What color is the sky?');
        
        $result = $template->render();
        
        self::assertStringContainsString('The sky is blue', $result);
        self::assertStringContainsString('What color is the sky?', $result);
    }

    public function test_render_json(): void
    {
        $template = new PromptTemplate('Hello {{ name }}');
        $template->with('name', 'World');
        
        $result = $template->renderJson();
        
        self::assertEquals('"Hello World"', $result);
    }
}

class ToolParametersTest extends TestCase
{
    public function test_string_parameter(): void
    {
        $param = new StringParameter(
            name: 'name',
            description: 'The name',
            required: true,
        );
        
        $array = $param->toArray();
        
        self::assertEquals('string', $array['type']);
        self::assertEquals('The name', $array['description']);
        self::assertTrue($array['default'] ?? false);
    }

    public function test_string_parameter_with_default(): void
    {
        $param = new StringParameter(
            name: 'name',
            description: 'The name',
            default: 'default_value',
        );
        
        $array = $param->toArray();
        
        self::assertEquals('default_value', $array['default']);
    }

    public function test_integer_parameter(): void
    {
        $param = new IntegerParameter(
            name: 'age',
            description: 'The age',
            minimum: 0,
            maximum: 150,
        );
        
        $array = $param->toArray();
        
        self::assertEquals('integer', $array['type']);
        self::assertEquals(0, $array['minimum']);
        self::assertEquals(150, $array['maximum']);
    }

    public function test_boolean_parameter(): void
    {
        $param = new BooleanParameter(
            name: 'active',
            description: 'Is active',
            default: true,
        );
        
        $array = $param->toArray();
        
        self::assertEquals('boolean', $array['type']);
        self::assertTrue($array['default']);
    }

    public function test_enum_parameter(): void
    {
        $param = new EnumParameter(
            name: 'status',
            description: 'Status',
            enumValues: ['active', 'inactive', 'pending'],
        );
        
        $array = $param->toArray();
        
        self::assertEquals('string', $array['type']);
        self::assertContains('active', $array['enum']);
    }

    public function test_enum_parameter_requires_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new EnumParameter(
            name: 'status',
            description: 'Status',
            enumValues: [],
        );
    }

    public function test_array_parameter(): void
    {
        $itemsParam = new StringParameter(
            name: 'item',
            description: 'An item',
        );
        
        $param = new ArrayParameter(
            name: 'items',
            description: 'List of items',
            items: $itemsParam,
            minItems: 1,
            maxItems: 10,
        );
        
        $array = $param->toArray();
        
        self::assertEquals('array', $array['type']);
        self::assertEquals(1, $array['minItems']);
        self::assertEquals(10, $array['maxItems']);
    }

    public function test_object_parameter(): void
    {
        $nameParam = new StringParameter(
            name: 'name',
            description: 'Name',
            required: true,
        );
        
        $ageParam = new IntegerParameter(
            name: 'age',
            description: 'Age',
        );
        
        $param = new ObjectParameter(
            name: 'person',
            description: 'A person',
            properties: [$nameParam, $ageParam],
        );
        
        $array = $param->toArray();
        
        self::assertEquals('object', $array['type']);
        self::assertArrayHasKey('name', $array['properties']);
        self::assertArrayHasKey('age', $array['properties']);
        self::assertContains('name', $array['required']);
    }
}

class ToolTest extends TestCase
{
    public function test_callable_tool(): void
    {
        $tool = new CallableTool(
            name: 'greet',
            description: 'Greet someone',
            parameters: [
                new StringParameter('name', 'Name to greet'),
            ],
            handler: fn (array $args) => 'Hello ' . ($args['name'] ?? 'World'),
        );
        
        self::assertEquals('greet', $tool->getName());
        self::assertEquals('Greet someone', $tool->getDescription());
        
        $result = $tool->execute(['name' => 'John']);
        
        self::assertEquals('Hello John', $result);
    }

    public function test_tool_to_array(): void
    {
        $tool = new CallableTool(
            name: 'test_tool',
            description: 'A test tool',
            parameters: [
                new StringParameter('input', 'An input'),
            ],
            handler: fn (array $args) => $args['input'],
        );
        
        $array = $tool->toArray();
        
        self::assertEquals('function', $array['type']);
        self::assertEquals('test_tool', $array['function']['name']);
        self::assertArrayHasKey('parameters', $array['function']);
    }
}

class ToolCallTest extends TestCase
{
    public function test_from_array_with_string_arguments(): void
    {
        $call = ToolCall::fromArray([
            'id' => 'call_123',
            'name' => 'greet',
            'arguments' => '{"name": "John"}',
        ]);
        
        self::assertEquals('call_123', $call->id);
        self::assertEquals('greet', $call->name);
        self::assertIsArray($call->arguments);
        self::assertEquals('John', $call->arguments['name']);
    }

    public function test_from_array_with_array_arguments(): void
    {
        $call = ToolCall::fromArray([
            'id' => 'call_123',
            'name' => 'greet',
            'arguments' => ['name' => 'John'],
        ]);
        
        self::assertEquals('call_123', $call->id);
        self::assertEquals('John', $call->arguments['name']);
    }
}

class ToolResultTest extends TestCase
{
    public function test_to_content_with_string(): void
    {
        $result = new ToolResult(
            toolCallId: 'call_123',
            result: 'Hello World',
        );
        
        self::assertEquals('Hello World', $result->toContent());
    }

    public function test_to_content_with_array(): void
    {
        $result = new ToolResult(
            toolCallId: 'call_123',
            result: ['message' => 'Hello'],
        );
        
        $content = $result->toContent();
        
        self::assertStringContainsString('message', $content);
    }

    public function test_to_content_with_error(): void
    {
        $result = new ToolResult(
            toolCallId: 'call_123',
            result: 'Something went wrong',
            isError: true,
        );
        
        $content = $result->toContent();
        
        self::assertStringContainsString('error', $content);
    }
}
