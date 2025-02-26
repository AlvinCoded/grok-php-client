<?php

declare(strict_types=1);

namespace GrokPHP\Enums;

/**
 * Enum Model
 * 
 * Defines the available model options that can be used with the Grok AI API.
 *
 * @package GrokPHP\Enums.
 * @see https://docs.x.ai/docs/models
 */
enum Model: string
{
    /**
     * Model type constants for Grok AI models.
     * 
     * @var string GROK_2_1212 Grok 2 base model
     * @var string GROK_2_VISION_1212 Grok 2 vision-enhanced model
     * @var string GROK_VISION_BETA Beta version of Grok vision model
     * @var string GROK_BETA Beta version of Grok 2 model
     */
    case GROK_2_1212 = 'grok-2-1212';
    case GROK_2_VISION_1212 = 'grok-2-vision-1212';
    case GROK_VISION_BETA = 'grok-vision-beta';
    case GROK_BETA = 'grok-2-beta';

    /**
     * Get the display name of the model.
     *
     * @return string The formatted display name of the model
     */
    public function displayName(): string
    {
        return match($this) {
            self::GROK_2_1212        => 'Grok 2 (1212)',
            self::GROK_2_VISION_1212 => 'Grok 2 Vision (1212)',
            self::GROK_VISION_BETA   => 'Grok Vision Beta',
            self::GROK_BETA          => 'Grok Beta'
        };
    }

    /**
     * Creates a Model enum instance from a string value.
     *
     * @param string $value The model identifier string to convert
     * @return self The corresponding Model enum instance
     * @throws \InvalidArgumentException If the provided string doesn't match any known model
     */
    public static function fromString(string $value): self
    {
        $aliases = [
            'grok-2'               => self::GROK_2_1212,
            'grok-2-latest'        => self::GROK_2_1212,
            'grok-2-vision'        => self::GROK_2_VISION_1212,
            'grok-2-vision-latest' => self::GROK_2_VISION_1212,
        ];

        $lower = strtolower($value);
    
        if (array_key_exists($lower, $aliases)) {
            return $aliases[$lower];
        }

        foreach (self::cases() as $case) {
            if ($case->value === $lower) {
                return $case;
            }
        }

        throw new \InvalidArgumentException("Unsupported model: $value");
    }

    /**
     * Checks if the current model instance supports vision capabilities.
     *
     * @return bool
     */
    public function supportsVision(): bool
    {
        return $this === self::GROK_2_VISION_1212 || $this === self::GROK_VISION_BETA;
    }

    /**
     * Indicates whether this model supports streaming responses.
     *
     * @return bool
     */
    public function supportsStreaming(): bool
    {
        return match($this) {
            self::GROK_2_1212 => true,
            self::GROK_BETA   => true,
            default           => false,
        };
    }

    /**
     * Get the context window size for the model
     * 
     * The context window defines the maximum number of tokens that can be processed
     * in a single request for each model variant.
     *
     * @return int
     */
    public function contextWindow(): int
    {
        return match($this) {
            self::GROK_2_1212 => 32768,
            self::GROK_2_VISION_1212 => 131072,
            self::GROK_VISION_BETA => 8192,
            self::GROK_BETA => 131072,
        };
    }

    /**
     * Returns the default model instance
     * 
     * @return self
     */
    public static function default(): self
    {
        return self::GROK_2_1212;
    }
}
