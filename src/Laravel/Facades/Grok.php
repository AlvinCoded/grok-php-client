<?php

declare(strict_types=1);

namespace GrokPHP\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class Grok extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \GrokPHP\Client\GrokClient::class;
    }
}
