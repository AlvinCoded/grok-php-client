<?php

declare(strict_types=1);

namespace Tests\Integration;

use GrokPHP\Client\GrokClient;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Exceptions\GrokException;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
use GrokPHP\Params;

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

        $params = Params::create()
                ->temperature(0.8)
                ->maxTokens(200)
                ->systemMessage('You are a humorous AI assistant.');


        $response = $this->client->chat()->generate("Tell me a joke", $params);

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

        $params = Params::create()
                ->temperature(0.8)
                ->maxTokens(100)
                ->topP(0.9);


        $response = $this->client->chat()->generate("Write a short poem", $params);

        $this->assertInstanceOf(ChatMessage::class, $response);
        $this->assertNotEmpty($response->getContent());
    }

    public function testChatWithTokenUsage(): void
    {
        $response = $this->client->chat()->generate('Hello, how are you?');

        $usage = $response->getUsage();
        $this->assertIsArray($usage);
        $this->assertArrayHasKey('total_tokens', $usage);
        $this->assertArrayHasKey('prompt_tokens', $usage);
        $this->assertArrayHasKey('completion_tokens', $usage);
    }

    public function testChatWithSystemFingerprint(): void
    {
        $response = $this->client->chat()->generate('Tell me about yourself');

        $fingerprint = $response->getSystemFingerprint();
        $this->assertNotNull($fingerprint);
        $this->assertIsString($fingerprint);
    }

    public function testInvalidTemperature(): void
    {
        $this->expectException(GrokException::class);

        $params = Params::create()->temperature(2.5);

        $this->client->chat()->generate("What is the best carribean meal?", $params);
    }

    public function testLongConversationContext(): void
    {
        $longPrompt = str_repeat('Test message. ', 1000);
        
        $response = $this->client->chat()->generate($longPrompt);
        
        $this->assertInstanceOf(ChatMessage::class, $response);
        $this->assertNotEmpty($response->getContent());
    }

    public function testChatCompletionFinishReason(): void
    {
        $params = Params::create()->maxTokens(50);

        $response = $this->client->chat()->generate("Write a very short story", $params);

        $this->assertNotNull($response->getFinishReason());
        $this->assertContains(
            $response->getFinishReason(),
            ['stop', 'length', 'content_filter']
        );
    }
}
