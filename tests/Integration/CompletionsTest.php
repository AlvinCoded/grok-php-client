<?php

declare(strict_types=1);

namespace Tests\Integration;

use GrokPHP\Client\GrokClient;
use GrokPHP\Models\ChatCompletion;
use GrokPHP\Exceptions\GrokException;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

class CompletionsTest extends TestCase
{
    private GrokClient $client;
    private string $apiKey;

    protected function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $this->apiKey = getenv('GROK_API_KEY') ?: '';
        if (empty($this->apiKey)) {
            $this->markTestSkipped('No API key available for integration tests');
        }
        $this->client = new GrokClient($this->apiKey);
    }

    public function testBasicCompletion(): void
    {
        $response = $this->client->completions()->create(
            'What is artificial intelligence?'
        );

        $this->assertInstanceOf(ChatCompletion::class, $response);
        $this->assertNotEmpty($response->getText());
        $this->assertGreaterThan(0, $response->getUsage()['total_tokens']);
    }

    public function testCompletionWithCustomParameters(): void
    {
        $response = $this->client->completions()->create(
            'Write a short poem about AI',
            [
                'temperature' => 0.8,
                'max_tokens' => 100,
                'top_p' => 0.9
            ]
        );

        $this->assertInstanceOf(ChatCompletion::class, $response);
        $this->assertLessThanOrEqual(100, $response->getUsage()['completion_tokens']);
    }

    public function testStreamingCompletion(): void
    {
        $chunks = [];
        $callback = function ($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        };

        $this->client->completions()->stream(
            'Explain quantum computing step by step',
            $callback
        );

        $this->assertNotEmpty($chunks);
        $this->assertGreaterThan(1, count($chunks));
    }

    public function testMultipleCompletions(): void
    {
        $responses = $this->client->completions()->createMultiple(
            'Generate a business name',
            3
        );

        $this->assertCount(3, $responses);
        foreach ($responses as $response) {
            $this->assertInstanceOf(ChatCompletion::class, $response);
        }
    }

    public function testCompletionWithSystemMessage(): void
    {
        $response = $this->client->completions()->create(
            'What is your purpose?',
            [
                'system_message' => 'You are a helpful AI assistant named Grok.'
            ]
        );

        $this->assertInstanceOf(ChatCompletion::class, $response);
        $this->assertStringContainsString('Grok', $response->getText());
    }

    public function testCompletionWithInvalidParameters(): void
    {
        $this->expectException(GrokException::class);
        
        $this->client->completions()->create(
            'Test prompt',
            ['temperature' => 3.0]
        );
    }

    public function testCompletionTokenCount(): void
    {
        $prompt = str_repeat('Test ', 1000);
        $response = $this->client->completions()->create($prompt);

        $this->assertGreaterThan(0, $response->getUsage()['prompt_tokens']);
        $this->assertLessThanOrEqual(128000, $response->getUsage()['total_tokens']);
    }

    public function testCompletionModelBehavior(): void
    {
        $responses = [];
        
        foreach ([0.2, 0.8] as $temp) {
            $responses[] = $this->client->completions()->create(
                'Write a creative story about a talking stone',
                ['temperature' => $temp]
            );
        }

        $this->assertNotEquals(
            $responses[0]->getText(),
            $responses[1]->getText()
        );
    }
}
