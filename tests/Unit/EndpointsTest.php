<?php

declare(strict_types=1);

namespace Tests\Unit;

use GrokPHP\Config;
use GrokPHP\Endpoints\Chat;
use GrokPHP\Endpoints\Completions;
use GrokPHP\Endpoints\Images;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Models\ChatCompletion;
use GrokPHP\Models\Image;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class EndpointsTest extends TestCase
{
    private Client $httpClient;
    private Config $config;
    private MockHandler $mock;

    protected function setUp(): void
    {
        $this->mock = new MockHandler();
        $handlerStack = HandlerStack::create($this->mock);
        $this->httpClient = new Client(['handler' => $handlerStack]);
        $this->config = new Config();
    }

    public function testChatEndpoint(): void
    {
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'chat-123',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Test response'
                    ]
                ]
            ]
        ])));

        $chat = new Chat($this->config);
        $response = $chat->send('Test message');

        $this->assertInstanceOf(ChatMessage::class, $response);
        $this->assertEquals('Test response', $response->getContent());
    }

    public function testCompletionsEndpoint(): void
    {
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'compl-123',
            'choices' => [
                [
                    'message' => [
                        'content' => 'Test completion'
                    ]
                ]
            ]
        ])));

        $completions = new Completions($this->httpClient, $this->config);
        $response = $completions->create('Test prompt');

        $this->assertInstanceOf(ChatCompletion::class, $response);
        $this->assertEquals('Test completion', $response->getText());
    }

    public function testImagesEndpoint(): void
    {
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'img-123',
            'choices' => [
                [
                    'message' => [
                        'content' => 'Image analysis result'
                    ]
                ]
            ]
        ])));

        $requestBuilder = new \GrokPHP\Utils\RequestBuilder();
        $responseParser = new \GrokPHP\Utils\ResponseParser();
        $images = new Images($this->httpClient, $this->config, $requestBuilder, $responseParser);
        $response = $images->analyze('https://picsum.photos/200/300');

        $this->assertInstanceOf(Image::class, $response);
        $this->assertEquals('Image analysis result', $response->getContent());
    }

    public function testChatStreamingResponse(): void
    {
        $chunks = [];
        $callback = function ($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        };

        $this->mock->append(new Response(200, ['Content-Type' => 'text/event-stream'], "data: {\"chunk\": 1}\n\ndata: {\"chunk\": 2}\n\n"));

        $chat = new Chat($this->config);
        $chat->streamChat('Test message', ['callback' => $callback]);

        $this->assertCount(2, $chunks);
    }

    public function testCompletionWithInvalidModel(): void
    {
        $this->expectException(GrokException::class);
        
        $completions = new Completions($this->httpClient, $this->config);
        $completions->create('Test', ['model' => 'invalid-model']);
    }

    public function testImageAnalysisWithInvalidUrl(): void
    {
        $this->expectException(GrokException::class);
        
        $requestBuilder = new \GrokPHP\Utils\RequestBuilder();
        $responseParser = new \GrokPHP\Utils\ResponseParser();
        $images = new Images($this->httpClient, $this->config, $requestBuilder, $responseParser);
        $images->analyze('invalid-url');
    }

    public function testChatWithEmptyMessage(): void
    {
        $this->expectException(GrokException::class);
        
        $chat = new Chat($this->config);
        $chat->send('');
    }

    public function testCompletionWithTooManyTokens(): void
    {
        $this->expectException(GrokException::class);
        
        $completions = new Completions($this->httpClient, $this->config);
        $completions->create('Test', ['max_tokens' => 129000]);
    }

    public function testRateLimitHandling(): void
    {
        $this->mock->append(
            new Response(429, [], json_encode(['error' => 'Rate limit exceeded'])),
            new Response(200, [], json_encode([
                'id' => 'chat-123',
                'choices' => [['message' => ['content' => 'Success']]]
            ]))
        );

        $chat = new Chat($this->config);
        $response = $chat->send('Test message');

        $this->assertEquals('Success', $response->getContent());
    }

    public function testMultimodalMessageHandling(): void
    {
        $this->mock->append(new Response(200, [], json_encode([
            'id' => 'chat-123',
            'choices' => [
                [
                    'message' => [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Description'
                            ],
                            [
                                'type' => 'image',
                                'image_url' => ['url' => 'https://picsum.photos/200/300']
                            ]
                        ]
                    ]
                ]
            ]
        ])));

        $chat = new Chat($this->config);
        $response = $chat->send('Analyze this image');

        $this->assertInstanceOf(ChatMessage::class, $response);
    }
}
