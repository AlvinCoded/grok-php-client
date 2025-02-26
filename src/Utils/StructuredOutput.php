<?php

declare(strict_types=1);

namespace GrokPHP\Utils;

/**
 * Class StructuredOutput
 *
 * Helper for building structured output instructions for Grok.
 * It builds the proper response_format payload so that the API
 * will return a JSON string following the provided JSON Schema.
 *
 * @package GrokPHP\Utils.
 * @see https://docs.x.ai/docs/guides/structured-outputs
 */
class StructuredOutput
{
    private array $jsonSchema;
    private bool $strict;

    /**
     * StructuredOutput constructor.
     *
     * @param array $jsonSchema The JSON Schema the model should adhere to.
     * @param bool $strict Whether to enforce strict schema output.
     */
    public function __construct(array $jsonSchema, bool $strict = true)
    {
        $this->jsonSchema = $jsonSchema;
        $this->strict = $strict;
    }

    /**
     * Returns the response_format value used in API calls.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'strict' => $this->strict,
                'schema' => $this->jsonSchema,
            ],
        ];
    }
}
