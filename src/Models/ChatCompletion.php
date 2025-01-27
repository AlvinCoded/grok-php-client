<?php

declare(strict_types=1);

namespace GrokPHP\Models;

use GrokPHP\Exceptions\GrokException;
use GrokPHP\Traits\ValidatesInput;
use JsonSerializable;

/**
 * Class ChatCompletion
 * 
 * Represents a completion response from the Grok AI API.
 *
 * @package GrokPHP\Models
 * @author Alvin Panford <panfordalvin@gmail.com>
 * @see https://docs.x.ai/docs/api-reference#chat-completions
 */
class ChatCompletion implements JsonSerializable
{
    use ValidatesInput;
    
    /**
     * @var string The completion ID
     */
    private string $id;

    /**
     * @var string The object type
     */
    private string $object = 'chat.completion';

    /**
     * @var int The creation timestamp
     */
    private int $created;

    /**
     * @var string The provider name
     */
    private string $provider;

    /**
     * @var string The model used
     */
    private string $model;

    /**
     * @var array The completion choices
     */
    private array $choices;

    /**
     * @var array Token usage statistics
     */
    private array $usage;

    /**
     * @var string|null System fingerprint
     */
    private ?string $systemFingerprint;

    /**
     * ChatCompletion constructor.
     *
     * @param array $data Raw response data from the API
     * @throws GrokException
     */
    public function __construct(array $data)
    {
        $this->validateParams($data, ['choices']);
        $this->validateParameter('model', $data['model'] ?? 'grok-2-1212');

        if (!isset($data['choices']) || !is_array($data['choices'])) {
            throw new GrokException('Invalid completion format: missing choices array');
        }

        $this->id = $data['id'] ?? '';
        $this->created = $data['created'] ?? time();
        $this->provider = $data['provider'] ?? 'openrouter';
        $this->model = $data['model'] ?? 'grok-2-1212';
        $this->choices = $data['choices'];
        $this->usage = $data['usage'] ?? [];
        $this->systemFingerprint = $data['system_fingerprint'] ?? null;
    }

    /**
     * Get the completion text from the first choice.
     *
     * @return string
     */
    public function getText(): string
    {
        return $this->choices[0]['message']['content'] ?? '';
    }

    /**
     * Get all completion choices.
     *
     * @return array
     */
    public function getChoices(): array
    {
        return $this->choices;
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
            'total_tokens' => $this->usage['total_tokens'] ?? 0,
            'prompt_characters' => $this->usage['prompt_characters'] ?? 0,
            'response_characters' => $this->usage['response_characters'] ?? 0,
            'cost' => $this->usage['cost'] ?? 0.0,
            'latency_ms' => $this->usage['latency_ms'] ?? 0
        ];
    }

    /**
     * Get the finish reason for the completion.
     *
     * @return string|null
     */
    public function getFinishReason(): ?string
    {
        return $this->choices[0]['finish_reason'] ?? null;
    }

    /**
     * Get the model used for completion.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the completion ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
     * Get the provider name.
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
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
     * Convert completion to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'object' => $this->object,
            'created' => $this->created,
            'provider' => $this->provider,
            'model' => $this->model,
            'choices' => $this->choices,
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
     * Get string representation of the completion.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getText();
    }
}
