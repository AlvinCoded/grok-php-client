<?php

declare(strict_types=1);

namespace GrokPHP\Models;

use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Traits\ValidatesInput;
use JsonSerializable;

/**
 * Class Image
 * 
 * Represents an image analysis response from the Grok AI API.
 * This model handles the response from image understanding capabilities
 * through the chat completions endpoint.
 *
 * @package GrokPHP\Models.
 * @see https://docs.x.ai/docs/api-reference#chat-completions
 */
class Image implements JsonSerializable
{
    use ValidatesInput;

    /**
     * @var string The response ID
     */
    private string $id;

    /**
     * @var string The object type
     */
    private string $object = 'chat.completion';

    /**
     * @var int Creation timestamp
     */
    private int $created;

    /**
     * @var Model The model used for image analysis
     */
    private Model $model;

    /**
     * @var array The analysis choices/responses
     */
    private array $choices;

    /**
     * @var array Token usage statistics
     */
    private array $usage;

    /**
     * @var string|null The analyzed image URL
     */
    private ?string $imageUrl;

    /**
     * @var array|null Additional metadata about the image
     */
    private ?array $metadata;

    /**
     * Image constructor.
     *
     * @param array $data Raw response data from the API
     * @throws GrokException
     */
    public function __construct(array $data)
    {
        $this->validateParams($data, ['choices']);
        $this->validateAndSetData($data);
    }

    /**
     * Validate and set the response data.
     *
     * @param array $data
     * @throws GrokException
     */
    private function validateAndSetData(array $data): void
    {
        if (isset($data['choices'][0]['message']['content'])) {
            $this->validateImageUrl($this->extractImageUrl($data));
        }

        $this->id = $data['id'] ?? '';
        $this->created = $data['created'] ?? time();
        $this->model = Model::fromString($data['model'] ?? 'grok-2-1212');
        $this->choices = $data['choices'];
        $this->usage = $data['usage'] ?? [];
        $this->imageUrl = $this->extractImageUrl($data);
        $this->metadata = $data['metadata'] ?? null;
    }

    /**
     * Extract image URL from the response data.
     *
     * @param array $data
     * @return string|null
     */
    private function extractImageUrl(array $data): ?string
    {
        foreach ($data['choices'][0]['message']['content'] ?? [] as $content) {
            if (isset($content['type']) && $content['type'] === 'image') {
                return $content['image_url']['url'] ?? null;
            }
        }
        return null;
    }

    /**
     * Get the analysis text from the response.
     *
     * @return string
     */
    public function getAnalysis(): string
    {
        $content = $this->choices[0]['message']['content'] ?? [];
        if (is_array($content)) {
            $textContent = array_filter($content, fn($item) => $item['type'] === 'text');
            return implode(' ', array_column($textContent, 'text'));
        }
        return '';
    }

    /**
     * Get the image URL that was analyzed.
     *
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * Get the model used for analysis.
     *
     * @return Model
     */
    public function getModel(): string
    {
        return $this->model->value;
    }

    /**
     * Get token usage statistics.
     *
     * @return array
     */
    public function getUsage(): array
    {
        return [
            'prompt_tokens' => $this->usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $this->usage['completion_tokens'] ?? 0,
            'total_tokens' => $this->usage['total_tokens'] ?? 0
        ];
    }

    /**
     * Get additional metadata about the image.
     *
     * @return array|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Get the creation timestamp.
     *
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * Get the response ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'object' => $this->object,
            'created' => $this->created,
            'model' => $this->model,
            'choices' => $this->choices,
            'usage' => $this->usage,
            'image_url' => $this->imageUrl,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Implement JsonSerializable interface.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get string representation of the image analysis.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getAnalysis();
    }

    /**
     * Check if the analysis contains specific content.
     *
     * @param string $keyword
     * @return bool
     */
    public function containsContent(string $keyword): bool
    {
        return str_contains(strtolower($this->getAnalysis()), strtolower($keyword));
    }

    /**
     * Get the finish reason for the analysis.
     *
     * @return string|null
     */
    public function getFinishReason(): ?string
    {
        return $this->choices[0]['finish_reason'] ?? null;
    }
}
