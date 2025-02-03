<?php

declare(strict_types=1);

namespace Tests\Integration;

use GrokPHP\Client\GrokClient;
use GrokPHP\Models\Image;
use GrokPHP\Exceptions\GrokException;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

class ImagesTest extends TestCase
{
    private GrokClient $client;
    private string $testImageUrl = 'https://picsum.photos/200/300.jpg';

    protected function setUp(): void
    {
        $this->client = new GrokClient();
    }

    public function testBasicImageAnalysis(): void
    {
        $response = $this->client->images()->analyze(
            $this->testImageUrl
        );

        $this->assertInstanceOf(Image::class, $response);
        $this->assertNotEmpty($response->getAnalysis());
    }

    public function testImageAnalysisWithPrompt(): void
    {
        $response = $this->client->images()->analyze(
            $this->testImageUrl,
            'Describe the main objects in this image'
        );

        $this->assertInstanceOf(Image::class, $response);
        $this->assertNotEmpty($response->getAnalysis());
    }

    public function testImageAnalysisWithDifferentFormats(): void
    {
        $imageFormats = [
            'jpg' => 'https://picsum.photos/200/300.jpg',
            'png' => 'https://picsum.photos/200/300.png',
            'webp' => 'https://picsum.photos/200/300.webp'
        ];

        foreach ($imageFormats as $format => $url) {
            $response = $this->client->images()->analyze($url);
            $this->assertInstanceOf(Image::class, $response);
        }
    }

    public function testInvalidImageUrl(): void
    {
        $this->expectException(GrokException::class);
        
        $this->client->images()->analyze('invalid-url');
    }

    public function testImageAnalysisWithDetailedPrompt(): void
    {
        $response = $this->client->images()->analyze(
            $this->testImageUrl,
            'Analyze the lighting, composition, and mood of this image'
        );

        $this->assertInstanceOf(Image::class, $response);
        $this->assertStringContainsString(
            'light',
            strtolower($response->getAnalysis())
        );
    }

    public function testImageMetadata(): void
    {
        $response = $this->client->images()->analyze($this->testImageUrl);
        $metadata = $response->getMetadata();

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('model', $metadata);
    }

    public function testMultimodalResponse(): void
    {
        $response = $this->client->images()->analyze(
            $this->testImageUrl,
            'What objects do you see and their approximate locations?'
        );

        $this->assertInstanceOf(Image::class, $response);
        $this->assertNotNull($response->getImageUrl());
    }

    public function testImageAnalysisTokenUsage(): void
    {
        $response = $this->client->images()->analyze($this->testImageUrl);
        $usage = $response->getUsage();

        $this->assertIsArray($usage);
        $this->assertArrayHasKey('total_tokens', $usage);
        $this->assertGreaterThan(0, $usage['total_tokens']);
    }

    public function testConcurrentImageAnalysis(): void
    {
        $urls = [
            'https://picsum.photos/200/300.jpg',
            'https://picsum.photos/200/300.jpg',
            'https://picsum.photos/200/300.jpg'
        ];

        $responses = [];
        foreach ($urls as $url) {
            $responses[] = $this->client->images()->analyze($url);
        }

        foreach ($responses as $response) {
            $this->assertInstanceOf(Image::class, $response);
        }
    }
}
