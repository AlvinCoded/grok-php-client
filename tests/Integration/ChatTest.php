<?php

declare(strict_types=1);

namespace Tests\Integration;

use GrokPHP\Client\GrokClient;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Exceptions\GrokException;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

class ChatTest extends TestCase
{
    private GrokClient $client;
    private string $apiKey;

    protected function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $this->apiKey = getenv('GROK_API_KEY') ? getenv('GROK_API_KEY') : '';
        if (empty($this->apiKey)) {
            $this->markTestSkipped('No API key available for integration tests');
        }
        $this->client = new GrokClient($this->apiKey);
    }

    public function testBasicChatCompletion(): void
    {
        $response = $this->client->chat()->send('What is artificial intelligence?');

        $this->assertInstanceOf(ChatMessage::class, $response);
        $this->assertNotEmpty($response->getContent());
        $this->assertEquals('assistant', $response->getRole());
    }

    public function testChatCompletionWithSystemMessage(): void
    {
        $response = $this->client->chat()->send(
            'Tell me a joke',
            [
                'system_message' => 'You are a humorous AI assistant.'
            ]
        );

        $this->assertInstanceOf(ChatMessage::class, $response);
        $this->assertNotEmpty($response->getContent());
    }

    public function testStreamingChatCompletion(): void
    {
        $chunks = [];
        $callback = function ($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        };

        $this->client->chat()->streamChat('Explain quantum computing briefly', $callback);

        $this->assertNotEmpty($chunks);
        $this->assertGreaterThan(1, count($chunks));
    }

    public function testMultiTurnConversation(): void
    {
        $messages = [
            [
                'role' => 'user',
                'content' => 'What is the capital of France?'
            ],
            [
                'role' => 'assistant',
                'content' => 'The capital of France is Paris.'
            ],
            [
                'role' => 'user',
                'content' => 'What is its population?'
            ]
        ];

        $response = $this->client->chat()->conversation($messages);

        $this->assertInstanceOf(ChatMessage::class, $response);
        $this->assertNotEmpty($response->getContent());
    }

    public function testChatCompletionWithParameters(): void
    {
        $response = $this->client->chat()->send(
            'Write a short poem',
            [
                'temperature' => 0.8,
                'max_tokens' => 100,
                'top_p' => 0.9
            ]
        );

        $this->assertInstanceOf(ChatMessage::class, $response);
        $this->assertNotEmpty($response->getContent());
    }

    public function testChatWithTokenUsage(): void
    {
        $response = $this->client->chat()->send('Hello, how are you?');

        $usage = $response->getUsage();
        $this->assertIsArray($usage);
        $this->assertArrayHasKey('total_tokens', $usage);
        $this->assertArrayHasKey('prompt_tokens', $usage);
        $this->assertArrayHasKey('completion_tokens', $usage);
    }

    public function testChatWithSystemFingerprint(): void
    {
        $response = $this->client->chat()->send('Tell me about yourself');

        $fingerprint = $response->getSystemFingerprint();
        $this->assertNotNull($fingerprint);
        $this->assertIsString($fingerprint);
    }

    public function testInvalidTemperature(): void
    {
        $this->expectException(GrokException::class);

        $this->client->chat()->send(
            'Test message',
            ['temperature' => 2.5]
        );
    }

    public function testLongConversationContext(): void
    {
        $longPrompt = str_repeat('Test message. ', 1000);
        
        $response = $this->client->chat()->send($longPrompt);
        
        $this->assertInstanceOf(ChatMessage::class, $response);
        $this->assertNotEmpty($response->getContent());
    }

    public function testChatCompletionFinishReason(): void
    {
        $response = $this->client->chat()->send(
            'Write a very short story',
            ['max_tokens' => 50]
        );

        $this->assertNotNull($response->getFinishReason());
        $this->assertContains(
            $response->getFinishReason(),
            ['stop', 'length', 'content_filter']
        );
    }
}
