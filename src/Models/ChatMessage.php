<?php

declare(strict_types=1);

namespace GrokPHP\Models;

use GrokPHP\Traits\ValidatesInput;
use GrokPHP\Exceptions\GrokException;
use JsonSerializable;

/**
 * Class ChatMessage
 * 
 * Represents a chat message response from the Grok AI API.
 * Handles both standard chat responses and streaming responses.
 *
 * @package GrokPHP\Models
 * @author Alvin Panford <panfordalvin@gmail.com>
 * @see https://docs.x.ai/docs/api-reference#chat-completions
 */
class ChatMessage implements JsonSerializable
{
    use ValidatesInput;

    /**
     * @var string The message ID
     */
    private string $id;

    /**
     * @var string The model used for generation
     */
    private string $model;

    /**
     * @var array The message content and metadata
     */
    private array $choices;

    /**
     * @var int|null Timestamp of when the message was created
     */
    private ?int $created;

    /**
     * @var array Usage statistics for the request
     */
    private array $usage;

    /**
     * @var string|null The system fingerprint
     */
    private ?string $systemFingerprint;

    /**
     * ChatMessage constructor.
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
        $this->validateParameter('model', $data['model'] ?? 'grok-2-1212');
        
        if (!isset($data['choices']) || !is_array($data['choices'])) {
            throw new GrokException('Invalid response format: missing choices array');
        }

        $this->id = $data['id'] ?? '';
        $this->model = $data['model'] ?? 'grok-2-1212';
        $this->choices = $data['choices'];
        $this->created = $data['created'] ?? null;
        $this->usage = $data['usage'] ?? [];
        $this->systemFingerprint = $data['system_fingerprint'] ?? null;
    }

    /**
     * Get the primary message content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->choices[0]['message']['content'] ?? '';
    }

    /**
     * Get the role of the message (e.g., 'assistant', 'user').
     *
     * @return string
     */
    public function getRole(): string
    {
        return $this->choices[0]['message']['role'] ?? 'assistant';
    }

    /**
     * Get the finish reason for the message.
     *
     * @return string|null
     */
    public function getFinishReason(): ?string
    {
        return $this->choices[0]['finish_reason'] ?? null;
    }

    /**
     * Get the message ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the model used.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get all choices returned by the API.
     *
     * @return array
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * Get the creation timestamp.
     *
     * @return int|null
     */
    public function getCreated(): ?int
    {
        return $this->created;
    }

    /**
     * Get token usage statistics.
     *
     * @return array
     */
    public function getUsage(): array
    {
        return $this->usage;
    }

    /**
     * Get the system fingerprint.
     *
     * @return string|null
     */
    public function getSystemFingerprint(): ?string
    {
        return $this->systemFingerprint;
    }

    /**
     * Check if this is a streaming response chunk.
     *
     * @return bool
     */
    public function isStreamChunk(): bool
    {
        return isset($this->choices[0]['delta']);
    }

    /**
     * Get the content from a streaming response chunk.
     *
     * @return string
     */
    public function getStreamContent(): string
    {
        return $this->choices[0]['delta']['content'] ?? '';
    }

    /**
     * Check if this is the final chunk in a stream.
     *
     * @return bool
     */
    public function isStreamFinished(): bool
    {
        return $this->getFinishReason() !== null;
    }

    /**
     * Convert message to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model' => $this->model,
            'choices' => $this->choices,
            'created' => $this->created,
            'usage' => $this->usage,
            'system_fingerprint' => $this->systemFingerprint,
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
     * Get string representation of the message.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getContent();
    }

    /**
     * Get total tokens used in this message.
     *
     * @return int
     */
    public function getTotalTokens(): int
    {
        return $this->usage['total_tokens'] ?? 0;
    }

    /**
     * Get prompt tokens used.
     *
     * @return int
     */
    public function getPromptTokens(): int
    {
        return $this->usage['prompt_tokens'] ?? 0;
    }

    /**
     * Get completion tokens used.
     *
     * @return int
     */
    public function getCompletionTokens(): int
    {
        return $this->usage['completion_tokens'] ?? 0;
    }
}
