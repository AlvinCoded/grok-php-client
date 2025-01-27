<?php

declare(strict_types=1);

namespace GrokPHP\Traits;

use GrokPHP\Exceptions\ValidationException;

/**
 * Trait ValidatesInput
 * 
 * Provides input validation functionality for Grok AI API parameters.
 *
 * @package GrokPHP\Traits
 * @author Alvin Panford <panfordalvin@gmail.com>
 * 
 */
trait ValidatesInput
{
    /**
     * @var array Default parameter constraints
     */
    protected array $constraints = [
        'temperature'       => ['min' => 0.0,  'max' => 2.0],
        'top_p'             => ['min' => 0.0,  'max' => 1.0],
        'max_tokens'        => ['min' => 1,    'max' => 128000],
        'presence_penalty'  => ['min' => -2.0, 'max' => 2.0],
        'frequency_penalty' => ['min' => -2.0, 'max' => 2.0],
        'n'                 => ['min' => 1,    'max' => 10],
    ];

    /**
     * @var array Supported models
     */
    protected array $supportedModels = [
        'grok-beta',
        'grok-2-vision-1212',
        'grok-2-1212',
    ];

    /**
     * Validate input parameters against defined constraints.
     *
     * @param array $params Parameters to validate
     * @param array $required Required parameter keys
     * @throws ValidationException
     */
    protected function validateParams(array $params, array $required = []): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || $params[$param] === '') {
                throw new ValidationException("Missing required parameter: {$param}");
            }
        }

        foreach ($params as $key => $value) {
            $this->validateParameter($key, $value);
        }
    }

    /**
     * Validate a single parameter.
     *
     * @param string $key Parameter name
     * @param mixed $value Parameter value
     * @throws ValidationException
     */
    protected function validateParameter(string $key, mixed $value): void
    {
        switch ($key) {
            case 'model':
                $this->validateModel($value);
                break;
            case 'temperature':
            case 'top_p':
            case 'presence_penalty':
            case 'frequency_penalty':
                $this->validateFloat($key, $value);
                break;
            case 'max_tokens':
            case 'n':
                $this->validateInteger($key, $value);
                break;
            case 'stream':
                $this->validateBoolean($key, $value);
                break;
            case 'messages':
                $this->validateMessages($value);
                break;
        }
    }

    /**
     * Validate model name.
     *
     * @param string $model
     * @throws ValidationException
     */
    protected function validateModel(string $model): void
    {
        if (!in_array($model, $this->supportedModels, true)) {
            throw new ValidationException(
                "Unsupported model: {$model}. Supported models: " . implode(', ', $this->supportedModels)
            );
        }
    }

    /**
     * Validate float parameter.
     *
     * @param string $key
     * @param mixed $value
     * @throws ValidationException
     */
    protected function validateFloat(string $key, mixed $value): void
    {
        if (!is_numeric($value)) {
            throw new ValidationException("{$key} must be a number");
        }

        $float = (float) $value;
        if (isset($this->constraints[$key])) {
            if ($float < $this->constraints[$key]['min'] || $float > $this->constraints[$key]['max']) {
                throw new ValidationException(
                    "{$key} must be between {$this->constraints[$key]['min']} and {$this->constraints[$key]['max']}"
                );
            }
        }
    }

    /**
     * Validate integer parameter.
     *
     * @param string $key
     * @param mixed $value
     * @throws ValidationException
     */
    protected function validateInteger(string $key, mixed $value): void
    {
        if (!is_int($value) && !ctype_digit($value)) {
            throw new ValidationException("{$key} must be an integer");
        }

        $int = (int) $value;
        if (isset($this->constraints[$key])) {
            if ($int < $this->constraints[$key]['min'] || $int > $this->constraints[$key]['max']) {
                throw new ValidationException(
                    "{$key} must be between {$this->constraints[$key]['min']} and {$this->constraints[$key]['max']}"
                );
            }
        }
    }

    /**
     * Validate boolean parameter.
     *
     * @param string $key
     * @param mixed $value
     * @throws ValidationException
     */
    protected function validateBoolean(string $key, mixed $value): void
    {
        if (!is_bool($value)) {
            throw new ValidationException("{$key} must be a boolean");
        }
    }

    /**
     * Validate messages array.
     *
     * @param mixed $messages
     * @throws ValidationException
     */
    protected function validateMessages(mixed $messages): void
    {
        if (!is_array($messages)) {
            throw new ValidationException("Messages must be an array");
        }

        foreach ($messages as $message) {
            if (!is_array($message)) {
                throw new ValidationException("Each message must be an array");
            }

            if (!isset($message['role'], $message['content'])) {
                throw new ValidationException("Each message must have 'role' and 'content' keys");
            }

            if (!in_array($message['role'], ['system', 'user', 'assistant'], true)) {
                throw new ValidationException("Invalid message role. Must be 'system', 'user', or 'assistant'");
            }
        }
    }

    /**
     * Validate image URL.
     *
     * @param string $url
     * @throws ValidationException
     */
    protected function validateImageUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException("Invalid image URL format");
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new ValidationException(
                "Invalid image format. Allowed formats: " . implode(', ', $allowedExtensions)
            );
        }
    }

    /**
     * Sanitize and prepare parameters for API request.
     *
     * @param array $params
     * @return array
     */
    protected function prepareParams(array $params): array
    {
        return array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
