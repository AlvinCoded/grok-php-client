<?php

declare(strict_types=1);

namespace Tests\Unit;

use GrokPHP\Client\GrokClient;
use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Params;
use PHPUnit\Framework\TestCase;

class StructuredOutputTest extends TestCase
{
    private GrokClient $client;

    protected function setUp(): void
    {
        $apiKey = $_ENV['GROK_API_KEY'] ?? getenv('GROK_API_KEY');
        
        if (empty($apiKey)) {
            $this->markTestSkipped('GROK_API_KEY is not set in environment variables.');
        }
        
        $this->client = new GrokClient($apiKey);
    }

    public function testArrayBasedStructuredOutput(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
                'email' => ['type' => 'string']
            ],
            'required' => ['name', 'age']
        ];

        $response = $this->client
            ->model(Model::GROK_2_1212)
            ->chat()
            ->generateStructured(
                "Extract: John Doe, 30, john@example.com",
                $schema
            );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('age', $response);
        $this->assertEquals('John Doe', $response['name']);
        $this->assertEquals(30, $response['age']);
    }

    public function testClassBasedStructuredOutput(): void
    {
        $response = $this->client
            ->model(Model::GROK_2_1212)
            ->chat()
            ->generateStructured(
                "Extract: Alice Smith, 28, alice@example.com",
                UserData::class
            );

        $this->assertInstanceOf(UserData::class, $response);
        if (is_object($response)) {     
            $this->assertEquals('Alice Smith', $response->name);
            $this->assertEquals(28, $response->age);
            $this->assertEquals('alice@example.com', $response->email);
        } else {
            $this->fail('Expected response to be an object, got ' . gettype($response));
        }
    }

    public function testNestedStructuredOutput(): void
    {
        $response = $this->client
            ->model(Model::GROK_2_1212)
            ->chat()
            ->generateStructured(
                "Describe a smartphone: iPhone 15, Apple, 2023, 6.1\"",
                ProductInfo::class
            );

        $this->assertInstanceOf(ProductInfo::class, $response);
        if (is_object($response)) {
            $this->assertEquals('iPhone 15', $response->productName);
            $this->assertEquals('Apple', $response->manufacturer);
            $this->assertEquals(2023, $response->releaseYear);
        } else {
            $this->fail('Expected response to be an object, got ' . gettype($response));
        }
    }

    public function testStructuredOutputWithParameters(): void
    {
        $params = Params::create()
            ->temperature(0.2)
            ->maxTokens(200);

        $response = $this->client
            ->model(Model::GROK_2_1212)
            ->chat()
            ->generateStructured(
                "Extract: Bob Wilson, 45, bob@company.com",
                UserData::class,
                $params
            );

        $this->assertInstanceOf(UserData::class, $response);
        if (is_object($response)) {
            $this->assertEquals(45, $response->age);
        } else {
            $this->fail('Expected response to be an object, got ' . gettype($response));
        }
    }

    public function testInvalidSchemaHandling(): void
    {
        $this->expectException(GrokException::class);
        
        $this->client
            ->chat()
            ->generateStructured(
                "Invalid request",
                ['invalid' => 'schema']
            );
    }

    public function testStructuredOutputValidation(): void
    {
        $response = $this->client
            ->model(Model::GROK_2_1212)
            ->chat()
            ->generateStructured(
                "Extract partial data: Charlie Brown, 35",
                UserData::class
            );

        $this->assertInstanceOf(UserData::class, $response);
        if (is_object($response)) {
            $this->assertNull($response->email);
        } else {
            $this->fail('Expected response to be an object, got ' . gettype($response));
        }
    }
}