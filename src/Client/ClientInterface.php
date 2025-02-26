<?php

declare(strict_types=1);

namespace GrokPHP\Client;

use GrokPHP\Config;
use GrokPHP\Endpoints\Chat;
use GrokPHP\Endpoints\Completions;
use GrokPHP\Endpoints\Images;

/**
 * Interface ClientInterface
 * 
 * Defines the contract for Grok AI API client implementations.
 * This interface ensures consistent implementation across different client versions
 * and provides a stable API for dependency injection.
 * 
 * @package GrokPHP\Client.
 * @see https://docs.x.ai/docs/api-reference
 */
interface ClientInterface
{
    /**
     * Initialize a new Grok AI client instance
     *
     * @param string $apiKey The API key for authentication
     * @param array $options Additional configuration options
     */
    public function __construct(string $apiKey, array $options = []);

    /**
     * Get an instance of the Chat endpoint handler
     * Used for interactive conversations with Grok AI
     *
     * @return Chat
     */
    public function chat(): Chat;

    /**
     * Get an instance of the Completions endpoint handler
     * Used for text completion tasks
     *
     * @return Completions
     */
    public function completions(): Completions;

    /**
     * Get an instance of the Images endpoint handler
     * Used for image generation and manipulation
     *
     * @return Images
     */
    public function images(): Images;

    /**
     * Set a custom base URL for the API
     * Useful for testing or using different API environments
     *
     * @param string $url The base URL for the API
     * @return void
     */
    public function setBaseUrl(string $url): void;

    /**
     * Get the current API version being used
     *
     * @return string
     */
    public function getApiVersion(): string;

    /**
     * Set the API version to use
     *
     * @param string $version The API version to use
     * @return void
     */
    public function setApiVersion(string $version): void;

    /**
     * Get the current configuration object
     *
     * @return Config
     */
    public function getConfig(): Config;
}
