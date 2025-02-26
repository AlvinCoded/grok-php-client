<?php

declare(strict_types=1);

namespace GrokPHP\Utils;

use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Models\ChatCompletion;
use GrokPHP\Models\EmbeddingResponse;
use GrokPHP\Models\Image;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseParser
 * 
 * Parses and processes API responses from Grok AI endpoints.
 *
 * @package GrokPHP\Utils.
 */
class ResponseParser
{
    /**
     * @var array Known error response keys
     */
    private const ERROR_KEYS = ['error', 'errors', 'message'];

    /**
     * Parse a raw API response.
     *
     * @param ResponseInterface $response
     * @param string $expectedType Type of response expected ('chat', 'completion', 'image')
     * @return mixed
     * @throws GrokException
     */
    public function parse(ResponseInterface $response, string $expectedType): mixed
    {
        $data = $this->decodeResponse($response);
        $this->checkForErrors($data);

        return match ($expectedType) {
            'chat' => new ChatMessage($data),
            'completion' => new ChatCompletion($data),
            'image' => new Image($data),
            'embedding' => new EmbeddingResponse($data),
            default => throw new GrokException("Unknown response type: {$expectedType}"),
        };
    }

    /**
     * Parse a streaming response chunk.
     *
     * @param string $chunk
     * @return array|null
     */
    public function parseStreamChunk(string $chunk): ?array
    {
        if (empty($chunk)) {
            return null;
        }

        if (str_starts_with($chunk, 'data: ')) {
            $chunk = substr($chunk, 6);
        }

        if ($chunk === '[DONE]') {
            return ['done' => true];
        }

        $data = json_decode($chunk, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Decode the raw response body.
     *
     * @param ResponseInterface $response
     * @return array
     * @throws GrokException
     */
    private function decodeResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GrokException(
                'Failed to decode API response: ' . json_last_error_msg(),
                $response->getStatusCode()
            );
        }

        return $data;
    }

    /**
     * Check for error responses.
     *
     * @param array $data
     * @throws GrokException
     */
    private function checkForErrors(array $data): void
    {
        foreach (self::ERROR_KEYS as $key) {
            if (isset($data[$key])) {
                $message = is_array($data[$key]) 
                    ? $this->formatErrorMessage($data[$key])
                    : $data[$key];
                throw new GrokException($message);
            }
        }
    }

    /**
     * Format error messages from array.
     *
     * @param array $errors
     * @return string
     */
    private function formatErrorMessage(array $errors): string
    {
        if (isset($errors['message'])) {
            return $errors['message'];
        }

        return implode('; ', array_map(function ($error) {
            return is_array($error) ? ($error['message'] ?? json_encode($error)) : $error;
        }, $errors));
    }

    /**
     * Extract usage statistics from response.
     *
     * @param array $data
     * @return array
     */
    public function extractUsage(array $data): array
    {
        return $data['usage'] ?? [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];
    }

    /**
     * Extract system fingerprint from response.
     *
     * @param array $data
     * @return string|null
     */
    public function extractSystemFingerprint(array $data): ?string
    {
        return $data['system_fingerprint'] ?? null;
    }

    /**
     * Parse response headers.
     *
     * @param ResponseInterface $response
     * @return array
     */
    public function parseHeaders(ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower((string) $name)] = implode(', ', (array) $values);
        }

        return $headers;
    }

    /**
     * Check if response indicates rate limiting.
     *
     * @param ResponseInterface $response
     * @return bool
     */
    public function isRateLimited(ResponseInterface $response): bool
    {
        return $response->getStatusCode() === 429;
    }

    /**
     * Extract rate limit information from headers.
     *
     * @param ResponseInterface $response
     * @return array
     */
    public function getRateLimitInfo(ResponseInterface $response): array
    {
        return [
            'limit'     => (int) ($response->getHeaderLine('x-ratelimit-limit') ?: 0),
            'remaining' => (int) ($response->getHeaderLine('x-ratelimit-remaining') ?: 0),
            'reset'     => (int) ($response->getHeaderLine('x-ratelimit-reset') ?: 0),
        ];
    }

    /**
     * Parse multimodal response content.
     *
     * @param array $data
     * @return array
     */
    public function parseMultimodalContent(array $data): array
    {
        $content = [];
        foreach ($data['choices'][0]['message']['content'] ?? [] as $item) {
            if (isset($item['type'])) {
                $content[$item['type']][] = match ($item['type']) {
                    'text' => $item['text'],
                    'image' => $item['image_url']['url'] ?? null,
                    default => $item,
                };
            }
        }
        return $content;
    }

    /**
     * Check if response is a streaming response.
     *
     * @param ResponseInterface $response
     * @return bool
     */
    public function isStreamingResponse(ResponseInterface $response): bool
    {
        return str_contains(
            $response->getHeaderLine('content-type'),
            'text/event-stream'
        );
    }
}
