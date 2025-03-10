<?php

declare(strict_types=1);

namespace GrokPHP;

use GrokPHP\Exceptions\GrokException;

/**
 * Class Params
 * 
 * Represents the parameters for Grok AI model completions.
 *
 * @package GrokPHP.
 */
class Params
{
    /**
     * @var array The parameters array
     */
    private array $params = [];

    /**
     * Creates a new Params instance.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Sets the model to use for completion.
     *
     * @param string $model
     * @return self
     */
    public function model(string $model): self
    {
        $this->params['model'] = $model;
        return $this;
    }

    /**
     * Sets the temperature for sampling the next token.
     *
     * @param float $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function temperature(float $value): self
    {
        $this->validateRange($value, 0.0, 2.0, 'temperature');
        $this->params['temperature'] = $value;
        return $this;
    }

    /**
     * Sets the maximum number of tokens to generate in the completion.
     *
     * @param int $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function maxTokens(int $value): self
    {
        $this->validateRange($value, 1, 128000, 'max_tokens');
        $this->params['max_tokens'] = $value;
        return $this;
    }

    /**
     * Sets the top P value for nucleus sampling.
     *
     * @param float $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function topP(float $value): self
    {
        $this->validateRange($value, 0.0, 2.0, 'top_p');
        $this->params['top_p'] = $value;
        return $this;
    }

    /**
     * Sets the presence of streaming responses.
     *
     * @param bool $value
     * @return self
     */
    public function stream(bool $value = true): self
    {
        $this->params['stream'] = $value;
        return $this;
    }

    /**
     * Sets the system message for the AI model.
     *
     * @param string $message
     * @return self
     */
    public function systemMessage(string $message): self
    {
        $this->params['messages'][] = [
            'role' => 'system',
            'content' => $message,
        ];
        return $this;
    }

    /**
     * Sets the number of completions to generate.
     * 
     * @param int $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function n(int $value): self
    {
        $this->validateRange($value, 1, 10, 'n');
        $this->params['n'] = $value;
        return $this;
    }

    /**
     * Sets the presence penalty.
     *
     * @param float $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function presencePenalty(float $value): self
    {
        $this->validateRange($value, -2.0, 2.0, 'presence_penalty');
        $this->params['presence_penalty'] = $value;
        return $this;
    }

    /**
     * Sets the frequency penalty.
     *
     * @param float $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function frequencyPenalty(float $value): self
    {
        $this->validateRange($value, -2.0, 2.0, 'frequency_penalty');
        $this->params['frequency_penalty'] = $value;
        return $this;
    }

    /**
     * Sets the best of parameter.
     *
     * @param int $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function bestOf(int $value): self
    {
        $this->validateRange($value, 1, 10, 'best_of');
        $this->params['best_of'] = $value;
        return $this;
    }

    /**
     * Sets the logit bias for the completion.
     *
     * @param array $values
     * @return self
     */
    public function logitBias(array $values): self
    {
        $this->params['logit_bias'] = $values;
        return $this;
    }

    /**
     * Sets the stop sequence for the completion.
     *
     * @param array $values
     * @return self
     */
    public function stop(array $values): self
    {
        $this->params['stop'] = $values;
        return $this;
    }

    /**
     * Sets the logprobs parameter.
     *
     * @param int $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function logprobs(int $value): self
    {
        $this->validateRange($value, 0, 5, 'logprobs');
        $this->params['logprobs'] = $value;
        return $this;
    }

    /**
     * Sets the dimensions parameter for embedding.
     * 
     * @param int $value
     * @return self
     * @throws \InvalidArgumentException
     */
    public function dimensions(int $value): self
    {
        $this->validateRange($value, 1, 2048, 'dimensions');
        $this->params['dimensions'] = $value;
        return $this;
    }

    /**
     * Sets the echo parameter.
     *
     * @param bool $value
     * @return self
     */
    public function echo(bool $value = true): self
    {
        $this->params['echo'] = $value;
        return $this;
    }

    /**
     * Sets the user parameter.
     *
     * @param string $value
     * @return self
     */
    public function user(string $value): self
    {
        $this->params['user'] = $value;
        return $this;
    }

    /**
     * Sets the suffix that is appended to the completion.
     * 
     * @param string $value
     * @return self
     */
    public function suffix(string $value): self
    {
        $this->params['suffix'] = $value;
        return $this;
    }

    /**
     * Returns the object's parameters as an array.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->params;
    }

    /**
     * Validates the given value is within the specified range.
     *
     * @param int|float $value
     * @param int|float $min
     * @param int|float $max
     * @param string $param
     * @throws GrokException
     */
    private function validateRange($value, $min, $max, string $param): void
    {
        if ($value < $min || $value > $max) {
            throw new GrokException("$param must be between $min and $max");
        }
    }
}
