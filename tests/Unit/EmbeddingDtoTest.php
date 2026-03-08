<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use LaravelLocalLlm\DTO\EmbeddingRequest;
use LaravelLocalLlm\DTO\EmbeddingResponse;
use LaravelLocalLlm\DTO\Embedding;

class EmbeddingDtoTest extends TestCase
{
    public function test_embedding_request_can_be_created(): void
    {
        $request = new EmbeddingRequest(
            model: 'text-embedding-3-small',
            input: 'Hello world'
        );

        $this->assertSame('text-embedding-3-small', $request->model);
        $this->assertSame('Hello world', $request->input);
    }

    public function test_embedding_request_with_array_input(): void
    {
        $request = new EmbeddingRequest(
            model: 'text-embedding-3-small',
            input: ['Hello world', 'Goodbye world']
        );

        $this->assertIsArray($request->input);
        $this->assertCount(2, $request->input);
    }

    public function test_embedding_request_to_array(): void
    {
        $request = new EmbeddingRequest(
            model: 'text-embedding-3-small',
            input: 'Hello world'
        );

        $arr = $request->toArray();

        $this->assertArrayHasKey('model', $arr);
        $this->assertArrayHasKey('input', $arr);
        $this->assertSame('text-embedding-3-small', $arr['model']);
    }

    public function test_embedding_response_creation(): void
    {
        $embeddings = [
            new Embedding(index: 0, embedding: [0.1, 0.2, 0.3]),
            new Embedding(index: 1, embedding: [0.4, 0.5, 0.6]),
        ];

        $response = new EmbeddingResponse(
            embeddings: $embeddings,
            model: 'text-embedding-3-small',
            totalTokens: 6
        );

        $this->assertCount(2, $response->embeddings);
        $this->assertSame('text-embedding-3-small', $response->model);
        $this->assertSame(6, $response->totalTokens);
    }

    public function test_embedding_to_array(): void
    {
        $embedding = new Embedding(
            index: 0,
            embedding: [0.1, 0.2, 0.3]
        );

        $arr = $embedding->toArray();

        $this->assertArrayHasKey('index', $arr);
        $this->assertArrayHasKey('embedding', $arr);
        $this->assertArrayHasKey('object', $arr);
        $this->assertSame(0, $arr['index']);
        $this->assertSame('embedding', $arr['object']);
    }
}
