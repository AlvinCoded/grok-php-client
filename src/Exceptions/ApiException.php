<?php

declare(strict_types=1);

namespace GrokPHP\Exceptions;

/**
 * Class ApiException
 * 
 * Exception class for handling API-specific errors from Grok AI.
 * 
 * @package GrokPHP\Exceptions.
 */
class ApiException extends GrokException
{
    /**
     * @var array|null The raw API response data
     */
    private ?array $responseData;

    /**
     * ApiException constructor.
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param array|null $responseData Raw API response data
     * @param \Exception|null $previous Previous exception
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?array $responseData = null,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
    }

    /**
     * Get the raw API response data.
     *
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
