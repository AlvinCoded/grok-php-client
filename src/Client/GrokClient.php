<?php

declare(strict_types=1);

namespace GrokPHP\Client;

use GrokPHP\Endpoints\Chat;
use GrokPHP\Endpoints\Completions;
use GrokPHP\Endpoints\Embeddings;
use GrokPHP\Endpoints\Images;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Config;
use GrokPHP\Enums\Model;

/**
 * Class GrokClient
 * 
 * Main client class for interacting with the Grok AI API
 * 
 * @package GrokPHP\Client
 * @author Alvin Panford <panfordalvin@gmail.com>
 */
class GrokClient implements ClientInterface
{
    /**
     * @var string $apiKey The API key used for authentication with the X.AI API
     */
    private string $apiKey;

    /**
     * @var string $baseUrl The base URL for the X.AI API endpoints
     */
    private string $baseUrl = 'https://api.x.ai/v1';

    /**
     * @var Config $config Configuration object containing settings for the Grok client
     */
    private Config $config;

    /**
     * @var string|null $currentModel The current model to use for API requests
     */
    private ?Model $currentModel = null;

    /**
     * GrokClient constructor
     *
     * @param string $apiKey Grok AI API key
     * @param array $options Additional configuration options
     * @throws GrokException If the API key is not provided
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new GrokException('API key is required');
        }
        
        $this->apiKey = $apiKey;
        $this->config = new Config(array_merge($options, ['api_key' => $apiKey]));
    }

    public function model(Model $model): self
    {
        $this->currentModel = $model;
        return $this;
    }

    public function beginConvo(array $history = []): Chat
    {
        return $this->chat()->withHistory($history);
    }

    /**
     * Get the Chat endpoint instance
     *
     * @return Chat
     */
    public function chat(): Chat
    {
        return new Chat($this->config, $this->currentModel);
    }

    /**
     * Get the Completions endpoint instance
     *
     * @return Completions
     */
    public function completions(): Completions
    {
        return new Completions($this->config, $this->currentModel);
    }

    /**
     * Get the Images endpoint instance
     *
     * @return Images
     */
    public function images(): Images
    {
        return new Images($this->config, $this->currentModel);
    }

    /**
     * Get the Embeddings endpoint instance
     *
     * @return Embeddings
     */
    public function embeddings(): Embeddings
    {
        return new Embeddings($this->config, $this->currentModel);
    }

    /**
     * Set a custom base URL for the API
     *
     * @param string $url
     * @return void
     */
    public function setBaseUrl(string $url): void
    {
        $this->baseUrl = $url;
    }

    /**
     * Get the current API version
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->config->getApiVersion();
    }

    /**
     * Set the API version
     *
     * @param string $version
     * @return void
     */
    public function setApiVersion(string $version): void
    {
        $this->config->setApiVersion($version);
    }

    /**
     * Get the current configuration
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
