<?php

declare(strict_types=1);

namespace Tests\Unit;

use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Models\ChatCompletion;
use GrokPHP\Models\Image;
use PHPUnit\Framework\TestCase;

class ModelsTest extends TestCase
{
    private array $chatMessageData;
    private array $chatCompletionData;
    private array $imageData;

    protected function setUp(): void
    {
        $this->chatMessageData = [
            'id' => 'msg_123',
            'model' => 'grok-2-1212',
            'created' => time(),
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Test response'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30
            ],
            'system_fingerprint' => 'fp_123'
        ];

        $this->chatCompletionData = [
            'id' => 'compl_123',
            'object' => 'chat.completion',
            'created' => time(),
            'model' => 'grok-2-latest',
            'provider' => 'openrouter',
            'choices' => [
                [
                    'message' => [
                        'content' => 'Test completion'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 15,
                'completion_tokens' => 25,
                'total_tokens' => 40,
                'prompt_characters' => 100,
                'response_characters' => 150,
                'cost' => 0.001,
                'latency_ms' => 500
            ],
            'system_fingerprint' => 'fp_456'
        ];

        $this->imageData = [
            'id' => 'img_123',
            'created' => time(),
            'model' => 'grok-2-vision-1212',
            'choices' => [
                [
                    'message' => [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Image analysis result'
                            ],
                            [
                                'type' => 'image',
                                'image_url' => ['url' => 'https://example.com/image.jpg']
                            ]
                        ]
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 20,
                'completion_tokens' => 30,
                'total_tokens' => 50
            ]
        ];
    }

    public function testChatMessageCreation(): void
    {
        $message = new ChatMessage($this->chatMessageData);
        
        $this->assertEquals('msg_123', $message->getId());
        $this->assertEquals('grok-2-latest', $message->getModel());
        $this->assertEquals('Test response', $message->getContent());
        $this->assertEquals('assistant', $message->getRole());
        $this->assertEquals('stop', $message->getFinishReason());
        $this->assertEquals(30, $message->getTotalTokens());
        $this->assertEquals('fp_123', $message->getSystemFingerprint());
    }

    public function testChatCompletionCreation(): void
    {
        $completion = new ChatCompletion($this->chatCompletionData);
        
        $this->assertEquals('compl_123', $completion->getId());
        $this->assertEquals('grok-2-1212', $completion->getModel());
        $this->assertEquals('Test completion', $completion->getText());
        $this->assertEquals('openrouter', $completion->getProvider());
        $this->assertEquals(40, $completion->getUsage()['total_tokens']);
        $this->assertEquals(0.001, $completion->getUsage()['cost']);
        $this->assertEquals(500, $completion->getUsage()['latency_ms']);
    }

    /**
     * @covers \GrokPHP\Models\Image
     */
    public function testImageCreation(): void
    {
        $image = new Image($this->imageData);
        
        $this->assertEquals('img_123', $image->getId());
        $this->assertEquals('grok-2-vision-1212', $image->getModel());
        $this->assertEquals('Image analysis result', $image->getAnalysis());
        $this->assertEquals('https://example.com/image.jpg', $image->getImageUrl());
        $this->assertEquals(50, $image->getUsage()['total_tokens']);
    }

    public function testInvalidChatMessageData(): void
    {
        $this->expectException(GrokException::class);
        new ChatMessage(['invalid' => 'data']);
    }

    public function testInvalidChatCompletionData(): void
    {
        $this->expectException(GrokException::class);
        new ChatCompletion(['invalid' => 'data']);
    }

    public function testInvalidImageData(): void
    {
        $this->expectException(GrokException::class);
        new Image(['invalid' => 'data']);
    }

    public function testChatMessageStreamHandling(): void
    {
        $streamData = [
            'id' => 'msg_123',
            'choices' => [
                [
                    'delta' => [
                        'content' => 'Streaming content'
                    ]
                ]
            ]
        ];

        $message = new ChatMessage($streamData);
        $this->assertTrue($message->isStreamChunk());
        $this->assertEquals('Streaming content', $message->getStreamContent());
    }

    public function testChatCompletionJsonSerialization(): void
    {
        $completion = new ChatCompletion($this->chatCompletionData);
        $json = json_encode($completion);
        $decoded = json_decode($json, true);
        
        $this->assertIsString($json);
        $this->assertEquals('compl_123', $decoded['id']);
        $this->assertEquals('Test completion', $decoded['choices'][0]['message']['content']);
    }

    public function testImageContentChecking(): void
    {
        $image = new Image($this->imageData);
        
        $this->assertTrue($image->containsContent('analysis'));
        $this->assertFalse($image->containsContent('nonexistent'));
    }

    public function testChatMessageToString(): void
    {
        $message = new ChatMessage($this->chatMessageData);
        $this->assertEquals('Test response', (string)$message);
    }

    public function testChatCompletionToString(): void
    {
        $completion = new ChatCompletion($this->chatCompletionData);
        $this->assertEquals('Test completion', (string)$completion);
    }

    public function testImageToString(): void
    {
        $image = new Image($this->imageData);
        $this->assertEquals('Image analysis result', (string)$image);
    }

    public function testUsageStatistics(): void
    {
        $message = new ChatMessage($this->chatMessageData);
        
        $this->assertEquals(10, $message->getPromptTokens());
        $this->assertEquals(20, $message->getCompletionTokens());
        $this->assertEquals(30, $message->getTotalTokens());
    }

    public function testMultimodalContent(): void
    {
        $image = new Image($this->imageData);
        $metadata = $image->getMetadata();
        
        $this->assertIsArray($metadata);
        $this->assertEquals('https://example.com/image.jpg', $image->getImageUrl());
    }
}
