<h1 align="center">Grok PHP: The 2-in-1 PHP SDK for Grok AI</h1>

<p align="center">
    <a href="https://packagist.org/packages/alvincoded/grok-php-client">
      <img src="https://img.shields.io/packagist/v/alvincoded/grok-php-client" alt="Latest Version">
    </a>
    <a href="https://php.net">
      <img src="https://img.shields.io/badge/PHP-8.2%2B-blue" alt="PHP Version">
    </a>
    <a href="https://laravel.com">
      <img src="https://img.shields.io/badge/Laravel-11%2B-red" alt="PHP Version">
    </a>
    <a href="LICENSE.md">
      <img src="https://img.shields.io/badge/license-MIT-brightgreen" alt="License">
    </a>
</p>

Grok PHP is a 2-in-1 PHP SDK offering seamless integration with Grok AI API for both **framework-agnostic PHP** and **Laravel 11+** applications.

## Features

- **Dual Architecture**: Use as framework-agnostic PHP library or first-class Laravel package with extensive error handling
- **Full API Coverage**: Chat, completions, images, embeddings, and structured outputs
- **Modern PHP**: Strict types, enums, and attributes for schema definition
- **Laravel Integration**: Auto-discovery, config publishing, and facade support
- **Advanced Chat Capabilities:** Full support for multi-turn conversations and real-time streaming
- **Model Flexibility:** Support for multiple Grok models (Grok-2, Grok-2-Vision, etc.)
- **Enterprise Ready:** Secure API handling with proper authentication
- **Easy Configuration:** Simple setup with minimal dependencies

## Requirements

- PHP 8.2 or higher
- Laravel 11+ _(For Laravel applications)_
- [Composer](https://getcomposer.org)
- [Grok AI API key](https://docs.x.ai/docs/overview)

## Installation

Install Grok PHP via Composer:

```bash
composer require alvincoded/grok-php-client
```
<img src="https://img.shields.io/badge/new-brightgreen" alt="New" width='20'> _Do the following with __Laravel applications only__:_
```bash
php artisan grok:install
```
> **Note:** This command publishes the configuration file and adds the relevant environment variables to your `.env` file.


## Quick Start

__***Framework-agnostic PHP Usage :***__

##### _Chat Completion_

```php
use GrokPHP\Client\GrokClient;
use GrokPHP\Params;

$client = new GrokClient($apiKey);

// Simple chat
$response = $client->chat()->generate("Tell me a joke about AI");
echo $response->getContent();

// With system message
$response = $client->chat()->generate(
    "What's the best programming language?",
    Params::create()
    ->systemMessage('You are an experienced programmer.')
    ->temperature(0.7)
);

// Streaming response
$client->chat()->streamChat(
    'Tell me something about Grok PHP',
    function (ChatMessage $chunk) {
        echo $chunk->text();
    }
);

// Multi-turn conversation
$chat = $client->beginConvo();

$response = $chat->send('What is machine learning?');
echo $response->text();

$response = $chat->send('Give me an example');
echo $response->text();
```

##### _Text Completions_

```php
use GrokPHP\Client\GrokClient;
use GrokPHP\Params;

$client = new GrokClient($apiKey);

// Basic completion
$response = $client->completions()->create(
    "The future of AI will",
    Params::create()->maxTokens(100)->temperature(0.7)
);

// Multiple completions
$responses = $client->completions()->createMultiple(
    "Write a creative title for a sci-fi novel",
    3,
    Params::create()->temperature(1.0)
);

// Get token count
$tokenCount = $client->completions()->getTokenCount("Sample text");
```

##### _Image Understanding_

```php
use GrokPHP\Client\GrokClient;
use GrokPHP\Params;

$client = new GrokClient($apiKey);

// Basic image analysis
$response = $client->images()->analyze('https://picsum.photos/200/300');

// Detailed analysis with prompt
$response = $client->images()->analyze(
    'https://picsum.photos/200/300',
    'What objects can you identify in this image?',
    Params::create()->maxTokens(300)->temperature(0.8)
);

// Check image content
$containsPeople = $response->containsContent('person');
```
##### _Embeddings_

```php
use GrokPHP\Client\GrokClient;

$client = new GrokClient($apiKey);

$embeddingResponse = $client->embeddings()->create('Hello, world!');
$embeddings = $embeddingResponse->getEmbeddings();
```

##### _Model-specific executions_

```php
use GrokPHP\Client\GrokClient;
use GrokPHP\Enums\Model;

$client = new GrokClient($apiKey);

// Simple chat (with model specification)
$response = $client->model(Model::GROK_2_1212)->generate('Tell me a joke about AI');
echo $response->text();

// Get model capabilities
$model = Model::GROK_2_1212
$config = $client->getConfig();

echo $config->getModelMaxTokens($model)      // 32,768
echo $config->modelSupportsStreaming($model) // true
echo $config->modelSupportsFunctions($model) // false
```

##### _Structured Output_
```php
use GrokPHP\Client\GrokClient;
use GrokPHP\Enums\Model;

// Scenario example: A university library needs to process 50,000 research papers into their new digital repository.
// Each entry requires consistent metadata fields.

// 1. Define schema once
$jsonSchema = [
    "type" => "object",
    "properties" => [
        "title" => ["type" => "string"],
        "authors" => ["type" => "array", "items" => ["type" => "string"]],
        "publication_year" => ["type" => "integer"],
        "doi" => ["type" => "string"],
        "keywords" => ["type" => "array", "items" => ["type" => "string"]],
        "citation_count" => ["type" => "integer"]
    ],
    "required" => ["title", "authors"]
];


// 2. Process documents
$client = new GrokClient($apiKey);

foreach ($researchPapers as $paperText) {
    $metadata = $client->chat()->generateStructured($paperText, $jsonSchema);
    
    // 3. Directly store structured data
    $this->database->insertPaper(
        title: $metadata['title'],
        authors: $metadata['authors'],
        year: $metadata['publication_year'] ?? null,
        doi: $metadata['doi'] ?? '',
        keywords: $metadata['keywords'] ?? []
    );
}
```

##### _Structured Output (alt. option with PHP class)_

```php
// Define your schema as a PHP class
class ResearchPaper extends \GrokPHP\Utils\DataModel 
{
    #[SchemaProperty(type: 'string', description: 'Paper title')]
    public string $title;
    
    #[SchemaProperty(type: 'array', description: 'List of authors')]
    public array $authors;
    
    #[SchemaProperty(type: 'integer', description: 'Year of publication', required: false)]
    public int $publicationYear;
}

// ...then, in your application code
$result = $client->chat()->generateStructured(
            "Extract research paper details", 
             ResearchPaper::class
          );

// ...and finally, get typed properties
echo $result->title;
echo $result->authors[0];

```

<img src="https://img.shields.io/badge/new-brightgreen" alt="New" width='20'> __***Laravel Usage :***__

The coolest part about using Laravel with Grok PHP? You don't have to learn any new tricks! Just use it the same way you would with the framework-agnostic PHP and you're good to go. It's like magic, but better! ✨

```php
use GrokPHP\Enums\Model;
use GrokPHP\Facades\Grok;
use GrokPHP\Client\GrokClient;
use GrokPHP\Params;

public function __construct(
private GrokClient $grok
) {}

public function analyzeImage(): Response
{
    return $this->grok->model(Model::GROK_2_VISION_1212)->images()->analyze('https://picsum.photos/200/300.jpg');
}

// Using the facade
public function ask(): Response
{
    $prompt = "Do you know the muffin man?";
    $params =  Params::create()->maxTokens(300)->temperature(0.8);

    return Grok::model(Model::GROK_2_1212)->chat()->generate($prompt, $params);
}
```


## Response Handling

#### _Chat/Completion Response Methods_

```php
$response->getContent();       // Get response content
$response->getRole();          // Get message role
$response->getFinishReason();  // Get completion finish reason
$response->getId();            // Get response ID
$response->getModel();         // Get model used
$response->getUsage();         // Get token usage statistics
```

#### _Image Analysis Response Methods_

```php
$response->getAnalysis();      // Get analysis text
$response->getImageUrl();      // Get analyzed image URL
$response->getMetadata();      // Get image metadata
$response->getUsage();         // Get token usage
```

#### _Embedding Response Methods_
```php
$response->getEmbeddings();    // Get embeddings
$response->getUsage();         // Get token usage
```

## Error Handling

```php
use GrokPHP\Exceptions\GrokException;

try {
    $response = $client->chat()->generate("Your prompt");
} catch (GrokException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Supported Models

| Model               | Supports Streaming | Supports Functions |
|---------------------|--------------------|--------------------|
| grok-beta           | Yes                | Yes                |
| grok-2-vision-1212  | No                 | No                 |
| grok-2-1212         | Yes                | Yes                |

<br>

## Supported Parameters


- `temperature(float $value)`: Sets the temperature for sampling the next token.
- `maxTokens(int $value)`: Sets the maximum number of tokens to generate in the completion.
- `topP(float $value)`: Sets the top P value for nucleus sampling.
- `stream(bool $value)`: Sets the presence of streaming responses.
- `systemMessage(string $message)`: Sets the system message for the AI model.
- `n(int $value)`: Sets the number of completions to generate.
- `presencePenalty(float $value)`: Sets the presence penalty.
- `frequencyPenalty(float $value)`: Sets the frequency penalty.
- `logitBias(array $values)`: Sets the logit bias for the completion.
- `stop(array $values)`: Sets the stop sequence for the completion.
- `logprobs(int $value)`: Sets the logprobs parameter.
- `dimensions(int $value)`: Sets the dimensions parameter for embedding.
- `echo(bool $value)`: Sets the echo parameter.
- `user(string $value)`: Sets the user parameter.
- `suffix(string $value)`: Sets the suffix that is appended to the completion.

<br>

## Environment Variables
Add the following to your `.env` file:
```bash
GROK_API_KEY=your-api-key

# Include if Laravel is used
GROK_DEFAULT_MODEL=grok-2-latest
GROK_BASE_URL=https://api.x.ai
```
<br>

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are highly aappreciated! Please see the [Contributing Guide](CONTRIBUTING.md) for details.

## Security

Please review the [security policy](SECURITY.md) on how to report security vulnerabilities.

## License

Grok PHP is an open-sourced software licensed under the [MIT license](LICENSE).

## Support

If you encounter any issues or have questions, please [open an issue](https://github.com/alvincoded/grok-php-client/issues) on the GitHub repository.

---
</br>

<p align='center'>Built with ❤️ for the AI community.</p>
