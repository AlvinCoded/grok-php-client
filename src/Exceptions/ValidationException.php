<?php

declare(strict_types=1);

namespace GrokPHP\Exceptions;

/**
 * Class ValidationException
 * 
 * Exception class for handling input validation errors.
 * 
 * @package GrokPHP\Exceptions
 * @author Alvin Panford <panfordalvin@gmail.com>
 */
class ValidationException extends GrokException
{
    /**
     * @var array The validation errors
     */
    private array $errors;

    /**
     * ValidationException constructor.
     *
     * @param string $message Error message
     * @param array $errors Validation errors
     * @param int $code Error code
     * @param \Exception|null $previous Previous exception
     */
    public function __construct(
        string $message = "",
        array $errors = [],
        int $code = 0,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are errors for a specific field.
     *
     * @param string $field
     * @return bool
     */
    public function hasErrorFor(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get errors for a specific field.
     *
     * @param string $field
     * @return array
     */
    public function getErrorsFor(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
}
