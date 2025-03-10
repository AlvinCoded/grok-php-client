<?php

declare(strict_types=1);

namespace Tests\Integration;

use GrokPHP\Client\GrokClient;
use GrokPHP\Models\ChatCompletion;
use GrokPHP\Exceptions\GrokException;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
use GrokPHP\Params;

class CompletionsTest extends TestCase
{
    private GrokClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = $_ENV['GROK_API_KEY'] ?? getenv('GROK_API_KEY');
        $this->client = new GrokClient($apiKey);
        
        if (empty($apiKey)) {
            $this->markTestSkipped('GROK_API_KEY is not set in environment variables.');
        }
    }

    public function testBasicCompletion(): void
    {
        $params = Params::create()->maxTokens(100);
        $response = $this->client->completions()->create('What is artificial intelligence?', $params);

        $this->assertInstanceOf(ChatCompletion::class, $response);
        $this->assertIsString($response->getText());
        $this->assertGreaterThan(0, $response->getUsage()['total_tokens']);
    }

    public function testCompletionWithCustomParameters(): void
    {
        $response = $this->client->completions()->create(
            'Write a short poem about AI',
            Params::create()->temperature(0.8)->maxTokens(100)->topP(0.9)
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
            $callback,
            Params::create()->maxTokens(100)
        );

        $this->assertNotEmpty($chunks, 'Expected streaming chunks, but none were received.');
        $this->assertGreaterThan(1, count($chunks), 'Expected more than one chunk in streaming response.');

        foreach ($chunks as $chunk) {
            $this->assertIsArray($chunk);
            $this->assertArrayHasKey('choices', $chunk);
            $this->assertNotEmpty($chunk['choices'][0]['message']['content']);
        }
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
            'What is your purpose and what is your name?',
            Params::create()->systemMessage('You are a helpful AI assistant and your name is Grok.')->maxTokens(100)
        );

        $this->assertInstanceOf(ChatCompletion::class, $response);
        $this->assertStringContainsString('Grok', $response->getText());
    }

    public function testCompletionWithInvalidParameters(): void
    {
        $this->expectException(GrokException::class);
        
        $this->client->completions()->create(
            'Test prompt',
            Params::create()->temperature(3.0)
        );
    }

    public function testCompletionTokenCount(): void
    {
        $prompt = str_repeat('Test ', 1000);
        $response = $this->client->completions()->create($prompt, Params::create()->maxTokens(100));

        $this->assertGreaterThan(0, $response->getUsage()['prompt_tokens']);
        $this->assertLessThanOrEqual(128000, $response->getUsage()['total_tokens']);
    }

    public function testCompletionModelBehavior(): void
    {
        $responses = [];
        
        foreach ([0.2, 0.8] as $temp) {
            $responses[] = $this->client->completions()->create(
                'Write a creative story about a talking stone',
                (new Params())->temperature($temp)->maxTokens(100)
            );
        }

        $this->assertNotEquals(
            $responses[0]->getText(),
            $responses[1]->getText()
        );
    }
}
