<?php

namespace Tests\Unit;

use GrokPHP\Utils\DataModel;
use GrokPHP\Utils\SchemaProperty;

class ProductInfo extends DataModel
{
    #[SchemaProperty(type: 'string')]
    public string $productName;
    
    #[SchemaProperty(type: 'string')]
    public string $manufacturer;
    
    #[SchemaProperty(type: 'integer')]
    public int $releaseYear;
    
    #[SchemaProperty(type: 'string', description: 'Screen size')]
    public string $screenSize;
    /**
     * Convert the ProductInfo object to an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'productName' => $this->productName,
            'manufacturer' => $this->manufacturer,
            'releaseYear' => $this->releaseYear,
            'screenSize' => $this->screenSize,
        ];
    }
}
