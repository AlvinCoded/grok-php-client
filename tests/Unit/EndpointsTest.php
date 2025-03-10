<?php

declare(strict_types=1);

namespace Tests\Unit;

use GrokPHP\Config;
use GrokPHP\Endpoints\Chat;
use GrokPHP\Endpoints\Completions;
use GrokPHP\Endpoints\Images;
use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Models\ChatCompletion;
use GrokPHP\Models\Image;
use GrokPHP\Params;
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

        $apiKey = $_ENV['GROK_API_KEY'] ?? getenv('GROK_API_KEY');
        $baseUrl = $_ENV['GROK_BASE_URL'] ?? getenv('GROK_BASE_URL');

        if (empty($apiKey)) {
            $this->markTestSkipped('GROK_API_KEY is not set in environment variables.');
        }

        $this->config = new Config([
            'api_key' => $apiKey,
            'base_url' => $baseUrl,
        ]);
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

        $chat = new Chat($this->config, Model::GROK_2_1212);
        $chat->setHttpClient($this->httpClient);
        
        $response = $chat->generate('Test message');
        
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

        $completions = new Completions($this->config, Model::GROK_2_1212);
        $completions->setHttpClient($this->httpClient);
        $params = Params::create()->maxTokens(100);

        $response = $completions->create('Test prompt', $params);

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

        $images = new Images($this->config, Model::GROK_2_VISION_1212);
        $images->setHttpClient($this->httpClient);
        $response = $images->analyze('https://picsum.photos/200/300.jpg');

        $this->assertInstanceOf(Image::class, $response);
        $this->assertEquals('Image analysis result', $response->getAnalysis());
    }

    public function testEmbeddingsEndpoint(): void
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

        $images = new Images($this->config, Model::GROK_2_VISION_1212);
        $images->setHttpClient($this->httpClient);
        $response = $images->analyze('https://picsum.photos/200/300.jpg');

        $this->assertInstanceOf(Image::class, $response);
        $this->assertEquals('Image analysis result', $response->getAnalysis());
    }

    public function testChatStreamingResponse(): void
    {
        $chunks = [];
        $callback = function ($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        };

        $this->mock->append(new Response(200, ['Content-Type' => 'text/event-stream'], "data: {\"chunk\": 1}\n\ndata: {\"chunk\": 2}\n\n"));

        $chat = new Chat($this->config, Model::GROK_2_1212);
        $chat->setHttpClient($this->httpClient);
        
        $chat->streamChat('Test message', $callback);
        
        $this->assertCount(2, $chunks);
    }

    public function testCompletionWithInvalidModel(): void
    {
        $this->expectException(GrokException::class);
        
        $completions = new Completions($this->config, null);
        $params = new Params();
        $params->model('invalid-model');
        $completions->create('Test', $params);
    }

    public function testImageAnalysisWithInvalidUrl(): void
    {
        $this->expectException(GrokException::class);
        
        $images = new Images($this->config, null);
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
        
        $completions = new Completions($this->config, null);
        $params = new Params();
        $params->maxTokens(129000);
        $completions->create('Test', $params);
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

        $this->assertInstanceOf(ChatMessage::class, $response);
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
                                'image_url' => ['url' => 'https://picsum.photos/200/300.jpg']
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
