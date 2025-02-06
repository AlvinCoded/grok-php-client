<?php

declare(strict_types=1);

namespace GrokPHP\Utils;

use Attribute;

/**
 * Attribute class for marking class properties as schema properties.
 * This attribute can only be applied to properties.
 * 
 * @see \Attribute
 * @package GrokPHP\Utils
 * @author Alvin Panford <panfordalvin@gmail.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]

class SchemaProperty
{
    /**
     * Constructs a new instance of the SchemaProperty class.
     * 
     * @param mixed ...$params 
     * @return void
     */
    public function __construct(
        public string $type = 'string',
        public bool $required = true,
        public ?string $description = null
    ) {}

    /**
     * Converts the SchemaProperty object to an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'description' => $this->description
        ]);
    }
}
