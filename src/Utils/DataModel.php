<?php

declare(strict_types=1);

namespace GrokPHP\Utils;

use ReflectionClass;

/**
 * Abstract base class for data models
 * 
 * This class serves as a foundation for creating data models in the application.
 * It provides common structure and functionality that all data models should inherit.
 *
 * @abstract DataModel
 * @package GrokPHP\Utils
 * @author Alvin Panford <panfordalvin@gmail.com>
 */
abstract class DataModel
{
    /**
     * Returns the schema definition for the data model.
     * 
     * The schema defines the structure and validation rules for the model's data fields.
     * 
     * @return array
     */
    public static function schema(): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ];

        $reflection = new ReflectionClass(static::class);
        
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(SchemaProperty::class);
            
            if (!empty($attributes)) {
                $attr = $attributes[0]->newInstance();
                $schema['properties'][$property->name] = $attr->toArray();
                
                if ($attr->required) {
                    $schema['required'][] = $property->name;
                }
            }
        }

        return $schema;
    }

    /**
     * Creates a new instance of the model from an array of data.
     * 
     * @param array $data
     * @return static
     */
    public function fromArray(array $data): static
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }
}
