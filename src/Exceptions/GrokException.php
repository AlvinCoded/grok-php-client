<?php

declare(strict_types=1);

namespace GrokPHP\Exceptions;

use Exception;

/**
 * Class GrokException
 * 
 * Base exception class for all Grok AI PHP package exceptions.
 * Provides common functionality and standardized error handling.
 *
 * @package GrokPHP\Exceptions
 * @author Alvin Panford <panfordalvin@gmail.com>
 */
class GrokException extends Exception
{
    /**
     * @var string|null Request ID associated with the error
     */
    protected ?string $requestId = null;

    /**
     * @var array Additional error context
     */
    protected array $context = [];

    /**
     * GrokException constructor.
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param \Exception|null $previous Previous exception
     * @param string|null $requestId Associated request ID
     * @param array $context Additional error context
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Exception $previous = null,
        ?string $requestId = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->requestId = $requestId;
        $this->context = $context;
    }

    /**
     * Get the request ID associated with the error.
     *
     * @return string|null
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Get additional error context.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Add context to the exception.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Create an exception from an API response.
     *
     * @param array $response API response data
     * @param int $code HTTP status code
     * @return self
     */
    public static function fromApiResponse(array $response, int $code = 0): self
    {
        $message = $response['error']['message'] ?? 'Unknown API error';
        $requestId = $response['error']['request_id'] ?? null;
        $context = [
            'type' => $response['error']['type'] ?? null,
            'param' => $response['error']['param'] ?? null,
            'code' => $response['error']['code'] ?? null,
        ];

        return new self($message, $code, null, $requestId, $context);
    }

    /**
     * Get a human-readable representation of the exception.
     *
     * @return string
     */
    public function __toString(): string
    {
        $output = sprintf(
            "[%s] %s (Code: %d)",
            $this->requestId ?? 'NO_REQUEST_ID',
            $this->message,
            $this->code
        );

        if (!empty($this->context)) {
            $output .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }

        return $output;
    }
}
