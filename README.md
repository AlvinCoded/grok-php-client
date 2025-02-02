<h1 align="center">Grok PHP: The Ultimate PHP Library for Grok AI</h1>
<p align="center">
    <a href="https://packagist.org/packages/alvincoded/php-grok-ai">
      <img src="https://img.shields.io/packagist/v/alvincoded/php-grok-ai" alt="Latest Version">
    </a>
    <a href="https://php.net">
      <img src="https://img.shields.io/badge/PHP-8.1%2B-blue" alt="PHP Version">
    </a>
    <a href="LICENSE.md">
      <img src="https://img.shields.io/badge/license-MIT-brightgreen" alt="License">
    </a>
</p>

Grok PHP is a robust, flexible, and feature-rich PHP library designed to interact seamlessly with the Grok AI API.

## Features

- **Seamless Integration:** Elegant PHP-first interface with intuitive methods for Grok AI
- **Advanced Chat Capabilities:** Full support for multi-turn conversations and real-time streaming
- **Comprehensive Features:** Text completions, image analysis, and embeddings all in one!
- **Robust Architecture:** Type-safe implementations with extensive error handling
- **Model Flexibility:** Support for multiple Grok models (Grok-2, Grok-2-Vision, etc.)
- **Enterprise Ready:** Secure API handling with proper authentication
- **Response Management:** Rich response objects with detailed analytics and metadata
- **Easy Configuration:** Simple setup with minimal dependencies

## Requirements

- PHP 8.1 or higher
- [Composer](https://getcomposer.org)
- [Grok AI API key](https://docs.x.ai/docs/overview)

## Installation

Install Grok PHP via Composer:

```bash
composer require alvincoded/php-grok-ai
```

## Quick Start

__Here's how simple it is to use Grok PHP :__  
<br>

#### _Chat Completion_

```php
<?php

require_once 'vendor/autoload.php';

use GrokPHP\Client\GrokClient;
use GrokPHP\Params;

$client = new GrokClient('your-api-key');

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

#### _Text Completions_

```php
<?php

require_once 'vendor/autoload.php';

use GrokPHP\Client\GrokClient;
use GrokPHP\Params;

$client = new GrokClient('your-api-key');

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

#### _Image Understanding_

```php
<?php

require_once 'vendor/autoload.php';

use GrokPHP\Client\GrokClient;
use GrokPHP\Params;

$client = new GrokClient('your-api-key');

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
#### _Embeddings_

```php
<?php

require_once 'vendor/autoload.php';

use GrokPHP\Client\GrokClient;

$client = new GrokClient('your-api-key');

$embeddingResponse = $client->embeddings()->create('Hello, world!');
$embeddings = $embeddingResponse->getEmbeddings();
```

#### _Model-specific executions_

```php
<?php

require_once 'vendor/autoload.php';

use GrokPHP\Client\GrokClient;
use GrokPHP\Enums\Model;

$client = new GrokClient('your-api-key');

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


## Response Handling

### Chat/Completion Response Methods

```php
$response->getContent();       // Get response content
$response->getRole();          // Get message role
$response->getFinishReason();  // Get completion finish reason
$response->getId();            // Get response ID
$response->getModel();         // Get model used
$response->getUsage();         // Get token usage statistics
```

### Image Analysis Response Methods

```php
$response->getAnalysis();      // Get analysis text
$response->getImageUrl();      // Get analyzed image URL
$response->getMetadata();      // Get image metadata
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
<br>

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
```
GROK_API_KEY=your-api-key
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

If you encounter any issues or have questions, please [open an issue](https://github.com/alvincoded/php-grok-ai/issues) on the GitHub repository.

---
</br>

<p align='center'>Built with ❤️ for the AI community.</p>