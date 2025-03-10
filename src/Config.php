<?php

declare(strict_types=1);

namespace GrokPHP;

use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;

/**
 * Class Config
 * 
 * Configuration management for Grok AI API client.
 *
 * @package GrokPHP.
 */
class Config
{
    /**
     * @var string Current API version
     */
    private string $apiVersion = '';

    /**
     * @var array Default configuration options
     */
    private array $defaults = [
        'timeout' => 30,
        'connect_timeout' => 10,
        'max_retries' => 3,
        'retry_delay' => 1000,
        'debug' => false,
        'stream_buffer_size' => 1024,
    ];

    /**
     * @var array Current configuration
     */
    private array $config;

    private function loadConfig(): array
    {
        return include __DIR__ . '/config/grok.php';
    }

    /**
     * Config constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->config = array_merge($this->defaults, $this->loadConfig(), $options);
    }

    /**
     * Get a configuration value.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key
     * @param mixed $value
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
     * Check if a model supports streaming.
     *
     * @param Model $model
     * @return bool
     * @throws GrokException
     */
    public function modelSupportsStreaming(Model $model): bool
    {
        return $model->supportsStreaming();
    }

    /**
     * Check if a model supports functions.
     *
     * @param Model $model
     * @return bool
     * @throws GrokException
     */
    public function modelSupportsFunctions(Model $model): bool
    {
        return $model->supportsVision();
    }

    /**
     * Get maximum tokens for a model.
     *
     * @param Model $model
     * @return int
     * @throws GrokException
     */
    public function getModelMaxTokens(Model $model): int
    {
        return $model->contextWindow();
    }

    /**
     * Sets the base URL in the configuration.
     *
     * @param string $url
     * @return void
     */
    public function setBaseUrl(string $url): void
    {
        $this->config['base_url'] = $url;
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
