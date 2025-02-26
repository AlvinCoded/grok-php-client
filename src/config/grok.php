<?php

use GrokPHP\Enums\Model;

return [

    /*
    |--------------------------------------------------------------------------
    | Grok API Key
    |--------------------------------------------------------------------------
    |
    | Your Grok API key is required to authenticate with the Grok AI API.
    |
    */

    'api_key' => env('GROK_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Grok AI API.  Modify this if you are using a
    | custom or internal endpoint, or for testing purposes.
    |
    */

    'base_url' => env('GROK_BASE_URL', 'https://api.x.ai'),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | Specify the default model to use when none is explicitly set. Valid model
    | values are provided by the Model enum (for example, "grok-2-1212", etc.).
    |
    */

    'default_model' => env('GROK_DEFAULT_MODEL', Model::GROK_2_1212->value),
];