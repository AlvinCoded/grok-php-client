<?php

declare(strict_types=1);

namespace GrokPHP\Traits;

use GrokPHP\Exceptions\GrokException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait HasApiOperations
 * 
 * Provides common API operation functionality for Grok AI endpoints.
 *
 * @package GrokPHP\Traits
 * @author Alvin Panford <panfordalvin@gmail.com>
 */
trait HasApiOperations
{
    /**
     * @var Client HTTP client instance
     */
    protected Client $httpClient;

    /**
     * @var string API key
     */
    protected string $apiKey;

    /**
     * @var array Default request options
     */
    protected array $defaultOptions = [
        RequestOptions::TIMEOUT => 30,
        RequestOptions::CONNECT_TIMEOUT => 10,
        RequestOptions::HTTP_ERRORS => true,
    ];

    /**
     * Make a GET request to the API.
     *
     * @param string $endpoint
     * @param array $params Query parameters
     * @param array $options Additional request options
     * @return array
     * @throws GrokException
     */
    protected function get(string $endpoint, array $params = [], array $options = []): array
    {
        $options[RequestOptions::QUERY] = $params;
        return $this->request('GET', $endpoint, $options);
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $endpoint
     * @param array $data Request body data
     * @param array $options Additional request options
     * @return array
     * @throws GrokException
     */
    protected function post(string $endpoint, array $data = [], array $options = []): array
    {
        $options[RequestOptions::JSON] = $data;
        return $this->request('POST', $endpoint, $options);
    }

    /**
     * Make a streaming request to the API.
     *
     * @param string $endpoint
     * @param array $data Request body data
     * @param callable $callback Function to handle each chunk
     * @param array $options Additional request options
     * @throws GrokException
     */
    protected function stream(string $endpoint, array $data, callable $callback, array $options = []): void
    {
        $options[RequestOptions::JSON] = $data;
        $options[RequestOptions::STREAM] = true;

        try {
            $response = $this->makeRequest('POST', $endpoint, $options);
            $body = $response->getBody();
            
            while (!$body->eof()) {
                $line = trim($body->read(1024));
                if (!empty($line)) {
                    $data = json_decode($line, true);
                    if ($data !== null) {
                        $callback($data);
                    }
                }
            }
        } catch (GuzzleException $e) {
            throw new GrokException("Stream request failed: {$e->getMessage()}", $e->getCode(), $e instanceof \Exception ? $e : null);
        }
    }

    /**
     * Make an API request with retry capability.
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array
     * @throws GrokException
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->makeRequest($method, $endpoint, $options);
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new GrokException("API request failed: {$e->getMessage()}", $e->getCode(), $e instanceof \Exception ? $e : null);
        }
    }

    /**
     * Make the actual HTTP request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function makeRequest(string $method, string $endpoint, array $options = []): ResponseInterface
    {
        $options = array_merge($this->defaultOptions, $options, [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ],
        ]);

        return $this->httpClient->request($method, $endpoint, $options);
    }

    /**
     * Handle the API response.
     *
     * @param ResponseInterface $response
     * @return array
     * @throws GrokException
     */
    protected function handleResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GrokException('Failed to decode API response');
        }

        return $data;
    }

    /**
     * Get the package version.
     *
     * @return string
     */
    protected function getVersion(): string
    {
        $composerFile = __DIR__ . '/../../composer.json';
        if (!file_exists($composerFile)) {
            throw new GrokException('composer.json file not found');
        }

        $composerData = json_decode(file_get_contents($composerFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GrokException('Failed to decode composer.json');
        }

        return $composerData['version'] ?? '1.0.0';
    }

    /**
     * Build query parameters for GET requests.
     *
     * @param array $params
     * @return string
     */
    protected function buildQueryString(array $params): string
    {
        return http_build_query(array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        }));
    }

    /**
     * Validate required parameters.
     *
     * @param array $params
     * @param array $required
     * @throws GrokException
     */
    protected function validateRequired(array $params, array $required): void
    {
        $missing = array_filter($required, function ($param) use ($params) {
            return !isset($params[$param]) || $params[$param] === '';
        });

        if (!empty($missing)) {
            throw new GrokException('Missing required parameters: ' . implode(', ', $missing));
        }
    }

    /**
     * Handle rate limiting and retries.
     *
     * @param callable $operation
     * @param int $maxRetries
     * @param int $delay Delay in milliseconds
     * @return mixed
     * @throws GrokException
     */
    protected function withRetry(callable $operation, int $maxRetries = 3, int $delay = 1000)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxRetries) {
            try {
                return $operation();
            } catch (GrokException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts === $maxRetries) {
                    break;
                }

                if ($e->getCode() === 429) { 
                    usleep($delay * 1000);
                    continue;
                }

                throw $e;
            }
        }

        throw new GrokException(
            "Operation failed after {$maxRetries} attempts: {$lastException->getMessage()}",
            $lastException->getCode(),
            $lastException
        );
    }
}
