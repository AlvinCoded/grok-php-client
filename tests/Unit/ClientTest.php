<?php

declare(strict_types=1);

namespace Tests\Unit;

use GrokPHP\Client\GrokClient;
use GrokPHP\Config;
use GrokPHP\Endpoints\Chat;
use GrokPHP\Endpoints\Completions;
use GrokPHP\Endpoints\Images;
use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private GrokClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = $_ENV['GROK_API_KEY'] ?? getenv('GROK_API_KEY');
        
        if (empty($apiKey)) {
            $this->markTestSkipped('GROK_API_KEY is not set in environment variables.');
        }
        
        $this->client = new GrokClient($apiKey);
    }


    /**
     * @covers \GrokPHP\Client\GrokClient
     */
    public function testClientInitialization(): void
    {
        $this->assertInstanceOf(GrokClient::class, $this->client);
    }

    public function testClientThrowsExceptionWithEmptyApiKey(): void
    {
        $originalEnv = $_ENV;

        unset($_ENV['GROK_API_KEY']);
        putenv('GROK_API_KEY');

        $this->expectException(GrokException::class);
        $this->expectExceptionMessage('API key is required');
        
        new GrokClient();

        $_ENV = $originalEnv;
    }

    public function testChatEndpointInitialization(): void
    {
        $chat = $this->client->chat();
        $this->assertInstanceOf(Chat::class, $chat);
    }

    public function testCompletionsEndpointInitialization(): void
    {
        $completions = $this->client->completions();
        $this->assertInstanceOf(Completions::class, $completions);
    }

    public function testImagesEndpointInitialization(): void
    {
        $images = $this->client->images();
        $this->assertInstanceOf(Images::class, $images);
    }

    public function testCustomBaseUrl(): void
    {
        $customUrl = 'https://jsonplaceholder.typicode.com/todos/1';
        $this->client->setBaseUrl($customUrl);
        
        $config = $this->client->getConfig();
        $this->assertEquals($customUrl, $config->getBaseUrl());
    }

    public function testApiVersionManagement(): void
    {
        $newVersion = 'v2';
        $this->client->setApiVersion($newVersion);
        $this->assertEquals($newVersion, $this->client->getApiVersion());
    }

    public function testConfigurationOptions(): void
    {
        $options = [
            'timeout' => 60,
            'max_retries' => 5,
        ];

        $client = new GrokClient($_ENV['GROK_API_KEY'], $options);
        $config = $client->getConfig();

        $this->assertEquals(60, $config->get('timeout'));
        $this->assertEquals(5, $config->get('max_retries'));
    }

    public function testDefaultConfigurationValues(): void
    {
        $config = $this->client->getConfig();
        
        $this->assertEquals(30, $config->get('timeout'));
        $this->assertEquals(10, $config->get('connect_timeout'));
        $this->assertEquals(3, $config->get('max_retries'));
    }

    public function testModelConfigurationAccess(): void
    {
        $config = $this->client->getConfig();
        
        $this->assertTrue($config->modelSupportsStreaming(Model::GROK_2_1212));
        $this->assertEquals(32768, $config->getModelMaxTokens(Model::GROK_2_1212));
    }

    public function testInvalidModelConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Model::fromString('invalid-model');
    }
}
