<?php

    declare(strict_types=1);

    namespace Tests\Integration;

    use GrokPHP\Client\GrokClient;
    use GrokPHP\Models\Image;
    use GrokPHP\Exceptions\GrokException;
    use PHPUnit\Framework\TestCase;
    use Dotenv\Dotenv;
use GrokPHP\Enums\Model;

    class ImagesTest extends TestCase
    {
        private string $testImageUrl = 'https://picsum.photos/200/300.jpg';
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

        public function testBasicImageAnalysis(): void
        {
            $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze($this->testImageUrl);

            $this->assertInstanceOf(Image::class, $response);
            $this->assertNotEmpty($response->getAnalysis());
        }

        public function testImageAnalysisWithPrompt(): void
        {
            $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze(
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
                $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze($url);
                $this->assertInstanceOf(Image::class, $response);
            }
        }

        public function testInvalidImageUrl(): void
        {
            $this->expectException(GrokException::class);
            
            $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze('invalid-url');
        }

        public function testImageAnalysisWithDetailedPrompt(): void
        {
            $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze(
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
            $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze($this->testImageUrl);

            $metadata = $response->getMetadata();

            $this->assertIsArray($metadata, 'Expected metadata to be an array.');
            $this->assertArrayHasKey('model', $metadata, 'Expected metadata to contain "model" key.');
            $this->assertArrayHasKey('usage', $metadata, 'Expected metadata to contain "usage" key.');
        }


        public function testMultimodalResponse(): void
        {
            $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze(
                $this->testImageUrl,
                'What objects do you see and their approximate locations?'
            );

            $this->assertInstanceOf(Image::class, $response);
            
            $imageUrl = $response->getImageUrl();
            $this->assertNotNull($imageUrl, 'Expected image URL to be present, but got null.');
            $this->assertMatchesRegularExpression(
                '/^https?:\/\/[^\s]+$/',
                $imageUrl,
                'Expected a valid image URL format.'
            );
        }


        public function testImageAnalysisTokenUsage(): void
        {
            $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze($this->testImageUrl);
            $usage = $response->getUsage();

            $this->assertIsArray($usage);
            $this->assertArrayHasKey('total_tokens', $usage);
            $this->assertGreaterThan(0, $usage['total_tokens']);
        }

        /**
         * @covers \GrokPHP\Endpoints\Images::analyze
         */
        public function testConcurrentImageAnalysis(): void
        {
            $urls = [
                'https://picsum.photos/200/300.jpg',
                'https://picsum.photos/200/300.jpg',
                'https://picsum.photos/200/300.jpg'
            ];

            $responses = [];
            foreach ($urls as $url) {
                $responses[] = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze($url);
            }

            foreach ($responses as $response) {
                $this->assertInstanceOf(Image::class, $response);
            }
        }

        /**
         * @covers \GrokPHP\Endpoints\Images::analyze
         */
        public function testImageAnalysisWithInvalidFormat(): void
        {
            $this->expectException(GrokException::class);
            
            $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze('https://picsum.photos/200/300.bmp');
        }

        public function testImageAnalysisWithEmptyPrompt(): void
        {
            $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze(
                $this->testImageUrl,
                ''
            );

            $this->assertInstanceOf(Image::class, $response);
            $this->assertNotEmpty($response->getAnalysis());
        }

        public function testImageAnalysisWithNullPrompt(): void
        {
            $response = $this->client->model(Model::GROK_2_VISION_1212)->images()->analyze(
                $this->testImageUrl,
                null
            );

            $this->assertInstanceOf(Image::class, $response);
            $this->assertNotEmpty($response->getAnalysis());
        }
}
