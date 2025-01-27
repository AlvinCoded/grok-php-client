<?php

declare(strict_types=1);

namespace GrokPHP;

use GrokPHP\Exceptions\GrokException;

/**
 * Class Config
 * 
 * Configuration management for Grok AI API client.
 *
 * @package GrokPHP
 * @author Alvin Panford <panfordalvin@gmail.com>
 */
class Config
{
    /**
     * @var string Current API version
     */
    private string $apiVersion = 'v1';

    /**
     * @var array Default configuration options
     */
    private array $defaults = [
        'timeout' => 30,
        'connect_timeout' => 10,
        'max_retries' => 3,
        'retry_delay' => 1000,
        'base_url' => 'https://api.x.ai',
        'debug' => false,
        'stream_buffer_size' => 1024,
    ];

    /**
     * @var array Model-specific configurations
     */
    private array $modelConfigs = [
        'grok-beta' => [
            'max_tokens' => 128000,
            'supports_streaming' => true,
            'supports_functions' => true,
        ],
        'grok-2-vision-1212' => [
            'max_tokens' => 4096,
            'supports_streaming' => false,
            'supports_functions' => false,
        ],
        'grok-2-1212' => [
            'max_tokens' => 128000,
            'supports_streaming' => true,
            'supports_functions' => true,
        ],
    ];

    /**
     * @var array Current configuration
     */
    private array $config;

    /**
     * Config constructor.
     *
     * @param array $options Custom configuration options
     */
    public function __construct(array $options = [])
    {
        $this->config = array_merge($this->defaults, $options);
    }

    /**
     * Get a configuration value.
     *
     * @param string $key Configuration key
     * @param mixed|null $default Default value if key doesn't exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Get the API key from the configuration.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->get('api_key', 'default_api_key');
    }

    /**
     * Get the current API version.
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * Set the API version.
     *
     * @param string $version
     * @return void
     */
    public function setApiVersion(string $version): void
    {
        $this->apiVersion = $version;
    }

    /**
     * Get the complete configuration array.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->config;
    }

    /**
     * Get model-specific configuration.
     *
     * @param string $model
     * @return array
     * @throws GrokException
     */
    public function getModelConfig(string $model): array
    {
        if (!isset($this->modelConfigs[$model])) {
            throw new GrokException("Unknown model: {$model}");
        }

        return $this->modelConfigs[$model];
    }

    /**
     * Check if a model supports streaming.
     *
     * @param string $model
     * @return bool
     * @throws GrokException
     */
    public function modelSupportsStreaming(string $model): bool
    {
        return $this->getModelConfig($model)['supports_streaming'];
    }

    /**
     * Check if a model supports functions.
     *
     * @param string $model
     * @return bool
     * @throws GrokException
     */
    public function modelSupportsFunctions(string $model): bool
    {
        return $this->getModelConfig($model)['supports_functions'];
    }

    /**
     * Get maximum tokens for a model.
     *
     * @param string $model
     * @return int
     * @throws GrokException
     */
    public function getModelMaxTokens(string $model): int
    {
        return $this->getModelConfig($model)['max_tokens'];
    }

    /**
     * Get the base URL for API requests.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return rtrim($this->get('base_url'), '/');
    }

    /**
     * Get timeout configurations.
     *
     * @return array
     */
    public function getTimeoutConfig(): array
    {
        return [
            'timeout' => $this->get('timeout'),
            'connect_timeout' => $this->get('connect_timeout'),
        ];
    }

    /**
     * Get retry configurations.
     *
     * @return array
     */
    public function getRetryConfig(): array
    {
        return [
            'max_retries' => $this->get('max_retries'),
            'retry_delay' => $this->get('retry_delay'),
        ];
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return (bool) $this->get('debug', false);
    }

    /**
     * Get the stream buffer size.
     *
     * @return int
     */
    public function getStreamBufferSize(): int
    {
        return (int) $this->get('stream_buffer_size', 1024);
    }
}
