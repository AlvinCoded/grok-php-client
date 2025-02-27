<?php

namespace Tests\Unit;

use GrokPHP\Utils\DataModel;
use GrokPHP\Utils\SchemaProperty;

class UserData extends DataModel 
{
    #[SchemaProperty(type: 'string', description: 'Full name of user')]
    public string $name;
    
    #[SchemaProperty(type: 'integer', description: 'Age in years')]
    public int $age;
    
    #[SchemaProperty(type: 'string', required: false)]
    public ?string $email = null;
}
