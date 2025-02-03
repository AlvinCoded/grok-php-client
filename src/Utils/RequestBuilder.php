<?php

declare(strict_types=1);

namespace GrokPHP\Utils;

use GrokPHP\Exceptions\GrokException;
use GrokPHP\Params;
use GrokPHP\Traits\ValidatesInput;

/**
 * Class RequestBuilder
 * 
 * Builds and validates API requests for Grok AI endpoints.
 *
 * @package GrokPHP\Utils
 */
class RequestBuilder
{
    use ValidatesInput;

    /**
     * @var array Default request options
     */
    private array $defaultOptions = [
        'temperature' => 0.7,
        'max_tokens' => 150,
        'top_p' => 1.0,
        'frequency_penalty' => 0.0,
        'presence_penalty' => 0.0,
        'stream' => false,
    ];

    /**
     * Build a chat request payload.
     *
     * @param array $messages
     * @param array $userParams
     * @param string $model
     * @return array
     * @throws GrokException
     */
    public function buildChatRequest(array $messages, array $userParams, string $model): array
    {
        $this->validateMessages($messages);

        $params = (new Params())->toArray();
        $payload = array_merge($params, $userParams);
        
        return array_merge($payload, [
            'model' => $model,
            'messages' => $this->formatMessages($messages),
        ]);
    }

    /**
     * Build a completion request payload.
     *
     * @param string $prompt
     * @param array $userParams
     * @param string $model
     * @return array
     * @throws GrokException
     */
    public function buildCompletionRequest(string $prompt, array $userParams, string $model): array
    {
        if (empty($prompt)) {
            throw new GrokException('Prompt cannot be empty');
        }

        $params = (new Params())->toArray();
        $payload = array_merge($params, $userParams);
        $this->validateParams($payload);

        return array_merge($payload, [
            'model' => $model,
            'prompt' => $prompt,
        ]);
    }

    /**
     * Build an image analysis request payload.
     *
     * @param string $imageUrl
     * @param string|null $prompt
     * @param array $userParams
     * @param string $model
     * @return array
     * @throws GrokException
     */
    public function buildImageAnalysisRequest(string $imageUrl, ?string $prompt = null, array $userParams, string $model): array
    {
        $this->validateImageUrl($imageUrl);
        $params = (new Params())->toArray();
        
        $messages = [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image',
                        'image_url' => ['url' => $imageUrl]
                    ],
                    [
                        'type' => 'text',
                        'text' => $prompt ?? 'Analyze this image.'
                    ]
                ]
            ]
        ];

        $payload = array_merge(
            $params,
            ['model' => $model],
            $userParams
        );

        return array_merge($payload, [
            'messages' => $messages,
        ]);
    }

    /**
     * Format messages for the API request.
     *
     * @param array $messages
     * @return array
     */
    private function formatMessages(array $messages): array
    {
        return array_map(function ($message) {
            if (is_string($message)) {
                return [
                    'role' => 'user',
                    'content' => $message
                ];
            }
            return $message;
        }, $messages);
    }

    /**
     * Build streaming request options.
     *
     * @param array $payload
     * @return array
     */
    public function buildStreamingOptions(array $payload): array
    {
        return [
            'stream' => true,
            'headers' => [
                'Accept' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
            ],
            'json' => array_merge($payload, ['stream' => true]),
        ];
    }

    /**
     * Build request headers.
     *
     * @param string $apiKey
     * @param array $additionalHeaders
     * @return array
     */
    public function buildHeaders(string $apiKey, array $additionalHeaders = []): array
    {
        return array_merge(
            [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Grok-PHP/1.0',
            ],
            $additionalHeaders
        );
    }

    /**
     * Build an embedding request payload.
     *
     * @param string|array $input
     * @param array $userParams
     * @param string $model
     * @return array
     * @throws GrokException
     */
    public function buildEmbeddingRequest(string|array $input, array $userParams, string $model): array
    {

        $params = (new Params())->toArray();

        $payload = array_merge(
            $params,
            ['model' => $model],
            $userParams
        );
        
        if (is_string($input)) {
            $payload['input'] = $input;
        } elseif (is_array($input)) {
            $payload['input'] = $input;
        } else {
            throw new GrokException('Invalid input type for embedding request');
        }

        return $payload;
    }

    /**
     * Build query parameters for GET requests.
     *
     * @param array $params
     * @return string
     */
    public function buildQueryString(array $params): string
    {
        return http_build_query(
            array_filter($params, fn($value) => $value !== null && $value !== '')
        );
    }

    /**
     * Set custom default options.
     *
     * @param array $options
     * @return void
     */
    public function setDefaultOptions(array $options): void
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

    /**
     * Get current default options.
     *
     * @return array
     */
    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    /**
     * Build request timeout options.
     *
     * @param int $timeout Request timeout in seconds
     * @param int $connectTimeout Connection timeout in seconds
     * @return array
     */
    public function buildTimeoutOptions(int $timeout = 30, int $connectTimeout = 10): array
    {
        return [
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
        ];
    }

    /**
     * Build retry options.
     *
     * @param int $maxRetries
     * @param int $retryDelay in milliseconds
     * @return array
     */
    public function buildRetryOptions(int $maxRetries = 3, int $retryDelay = 1000): array
    {
        return [
            'max_retries' => $maxRetries,
            'retry_delay' => $retryDelay,
            'retry_on_status' => [429, 500, 502, 503, 504],
        ];
    }
}
