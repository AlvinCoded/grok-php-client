# Grok PHP: The Ultimate PHP Wrapper for Grok AI API

Grok PHP is a robust, flexible, and feature-rich PHP package designed to interact seamlessly with the Grok AI API. This wrapper provides an intuitive interface to leverage the full power of Grok AI in your PHP applications with minimal code.

## Features

- Simple and intuitive API
- Full support for Grok AI Chat
- Text completions
- Image generation capabilities
- Robust error handling and validations

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

$client = new GrokClient('your-api-key');

// Simple chat
$response = $client->chat()->send("Tell me a joke about AI");
echo $response->getContent();

// With system message
$response = $client->chat()->send(
    "What's the best programming language?",
    [
        'system_message' => 'You are an experienced programmer.',
        'temperature' => 0.7
    ]
);

// Streaming response
$client->chat()->stream(
    "Explain quantum computing",
    function ($chunk) {
        echo $chunk['choices'][0]['message']['content'] ?? '';
        flush();
    }
);

// Multi-turn conversation
$messages = [
    ['role' => 'user', 'content' => 'What is machine learning?'],
    ['role' => 'assistant', 'content' => 'Machine learning is a subset of AI...'],
    ['role' => 'user', 'content' => 'Give me an example']
];

$response = $client->chat()->conversation($messages);
```

### Text Completions

```php
<?php

require_once 'vendor/autoload.php';

use GrokPHP\Client\GrokClient;

$client = new GrokClient('your-api-key');

// Basic completion
$response = $client->completions()->create(
    "The future of AI will",
    [
        'max_tokens' => 100,
        'temperature' => 0.7
    ]
);

// Multiple completions
$responses = $client->completions()->createMultiple(
    "Write a creative title for a sci-fi novel",
    3,
    ['temperature' => 1.0]
);

// Get token count - useful for understanding the token usage and limits.
$tokenCount = $client->completions()->getTokenCount("Sample text");
```

### Image Understanding

```php
<?php

require_once 'vendor/autoload.php';

use GrokPHP\Client\GrokClient;

$client = new GrokClient('your-api-key');

// Basic image analysis
$response = $client->images()->analyze('https://picsum.photos/200/300');

// Detailed analysis with prompt
$response = $client->images()->analyze(
    'https://picsum.photos/200/300',
    'What objects can you identify in this image?',
    [
        'temperature' => 0.8,
        'max_tokens' => 300
    ]
);

// Check image content
$containsPeople = $response->containsContent('person');
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
    $response = $client->chat()->send("Your prompt");
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

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are highly aappreciated! Please see the [Contributing Guide](CONTRIBUTING.md) for details.

## Security

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities.

## License

Grok PHP is open-sourced software licensed under the [MIT license](LICENSE).

## Support

If you encounter any issues or have questions, please [open an issue](https://github.com/alvincoded/php-grok-ai/issues) on the GitHub repository.

---
</br>

<p align='center'>Built with ❤️ for the AI community.</p>