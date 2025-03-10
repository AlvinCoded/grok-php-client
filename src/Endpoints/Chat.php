<?php

declare(strict_types=1);

namespace GrokPHP\Endpoints;

use GrokPHP\Config;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Params;
use GrokPHP\Enums\Model;
use GrokPHP\Traits\HasApiOperations;
use GrokPHP\Traits\ValidatesInput;
use GrokPHP\Utils\DataModel;
use GrokPHP\Utils\RequestBuilder;
use GrokPHP\Utils\ResponseParser;
use GrokPHP\Utils\StructuredOutput;
use GuzzleHttp\Client;

/**
 * Class Chat
 * 
 * Handles all chat-related operations with the Grok AI API.
 *
 * @package GrokPHP\Endpoints.
 * @see https://docs.x.ai/docs/api-reference#chat-completions
 */
class Chat
{
    use HasApiOperations, ValidatesInput;

    /**
     * @var Client The HTTP client instance
     */
    private Client $client;

    /**
     * @var Config The configuration instance
     */
    private Config $config;

    /**
     * @var Model The model to use for chat operations
     */
    private Model $model;

    /**
     * @var RequestBuilder The request builder instance
     */
    private RequestBuilder $requestBuilder;

    /**
     * @var ResponseParser The response parser instance
     */
    private ResponseParser $responseParser;

    /**
     * @var string The base endpoint for chat operations
     */
    private const CHAT_ENDPOINT = '/v1/chat/completions';
    
    /**
     * @var array Chat history
     */
    private array $history = [];

    /**
     * Chat constructor.
     *
     * @param Config $config
     * @param string|null $model
     */
    public function __construct(Config $config, ?Model $model = null)
    {
        $this->config = $config;
        $this->apiKey = $config->getApiKey();
        $this->httpClient = new Client();

        if (is_string($model)) {
            $this->model = Model::fromString($model);
        } elseif ($model instanceof Model) {
            $this->model = $model;
        } else {
            $this->model = Model::default();
        }

        $this->client = new Client([
            'base_uri' => $config->getBaseUrl(),
            'timeout' => $config->get('timeout'),
        ]);
        $this->requestBuilder = new RequestBuilder();
        $this->responseParser = new ResponseParser();
    }

    /**
     * Send a chat message to Grok AI.
     *
     * @param string $message The message to send.
     * @param Params|null $params Optional parameters for the request.
     * @return ChatMessage The response from the assistant.
     */
    public function send(string $message, ?Params $params = null): ChatMessage
    {
        if (empty($message)) {
            throw new GrokException('Message cannot be empty');
        }
        $this->history[] = ['role' => 'user', 'content' => $message];
        $response = $this->generate($this->history, $params);
        $this->history[] = ['role' => 'assistant', 'content' => $response->getContent()];
        return $response;
    }

    /**
     * Generates a chat message based on the given prompt and options.
     *
     * @param string|array $prompt The prompt to generate the chat message from. It can be a string or an array of strings.
     * @param Params|null $params Optional parameters for the request.
     * @return ChatMessage The generated chat message.
     */
    public function generate(string|array $prompt, ?Params $params = null): ChatMessage
    {
        $messages = $this->formatPrompt(prompt: $prompt);
        $payload = $this->requestBuilder->buildChatRequest(
            $messages,
            $params?->toArray() ?? [],
            $this->model->value
        );
        
        $response = $this->client->post(self::CHAT_ENDPOINT, [
            'json' => $payload,
            'headers' => $this->requestBuilder->buildHeaders($this->config->getApiKey())
        ]);
        
        return $this->responseParser->parse($response, 'chat');
    }

    /**
     * Generates a structured response using the specified JSON Schema.
     * The API response is automatically parsed into an associative array.
     *
     * @param string|array $prompt
     * @param array|string $jsonSchema The JSON Schema that constrains the output.
     * @param Params|null $params Additional parameters.
     * @return array|string Parsed JSON structure or raw text if parsing fails.
     * @throws GrokException
     */
    public function generateStructured(string|array $prompt, array|string $jsonSchema, ?Params $params = null): array|string
    {
        if (is_string($jsonSchema) && class_exists($jsonSchema)) {
            if (!is_subclass_of($jsonSchema, DataModel::class)) {
                throw new GrokException('Invalid schema class');
            }
            $schema = $jsonSchema::schema();
        } else {
            $schema = $jsonSchema;
        }

        $structuredOutput = new StructuredOutput($schema);
        $messages = $this->formatPrompt($prompt);
        $payload = $this->requestBuilder->buildChatRequest(
            $messages,
            $params?->toArray() ?? [],
            $this->model->value
        );

        $payload['response_format'] = $structuredOutput->toArray();

        $response = $this->client->post(self::CHAT_ENDPOINT, [
            'json' => $payload,
            'headers' => $this->requestBuilder->buildHeaders($this->config->getApiKey()),
        ]);

        $chatMessage = $this->responseParser->parse($response, 'chat');
        $decoded = json_decode($chatMessage->getContent(), true);

        if (is_string($jsonSchema) && class_exists($jsonSchema)) {
            return (new $jsonSchema())->fromArray($decoded)->toArray();
        }

        return $decoded;
    }

    /**
     * Sets the chat history.
     *
     * @param array $history An array containing the chat history.
     * @return self Returns the current instance for method chaining.
     */
    public function withHistory(array $history): self
    {
        $this->history = $history;
        return $this;
    }

    /**
     * Create a chat conversation with multiple messages.
     *
     * @param array $messages Array of message objects with role and content
     * @param Params|null $params Optional parameters for the request.
     * @return ChatMessage
     * @throws GrokException
     */
    public function conversation(array $messages, ?Params $params = null): ChatMessage
    {
        $payload = $this->requestBuilder->buildChatRequest(
            $messages,
            $params?->toArray() ?? [],
            $this->model->value
        );

        $response = $this->client->post(self::CHAT_ENDPOINT, [
            'json' => $payload
        ]);
        
        return $this->responseParser->parse($response, 'chat');
    }

    /**
     * Stream a chat response from Grok AI.
     *
     * @param string|array $prompt The prompt to send to the chat API.
     * @param callable $callback Function to handle each chunk of the stream.
     * @param array $options Additional options for the request.
     * @throws GrokException
     */
    public function streamChat(string|array $prompt, callable $callback, ?Params $params = null): void
    {
        $messages = $this->formatPrompt($prompt);
        $payload = $this->requestBuilder->buildChatRequest(
            $messages,
            ($params ?? Params::create()->stream())->toArray(),
            $this->model->value
        );

        $this->streamRequest(self::CHAT_ENDPOINT, $payload, $callback);
    }

    /**
     * Stream a request to the given endpoint.
     *
     * @param string $endpoint The endpoint to stream the request to.
     * @param array $payload The payload to send with the request.
     * @param callable $callback Function to handle each chunk of the stream.
     */
    private function streamRequest(string $endpoint, array $payload, callable $callback): void
    {
        $headers = $this->requestBuilder->buildHeaders($this->config->getApiKey());
        $response = $this->client->post($endpoint, [
            'json' => $payload,
            'headers' => $headers,
            'stream' => true,
        ]);

        $body = $response->getBody();
        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $lines = explode("\n", $chunk);
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $callback($line);
                }
            }
        }
    }

    /**
     * Formats the given prompt into an array of messages.
     *
     * @param string|array $prompt The prompt to format (can be a string or an array of messages).
     * @return array The formatted prompt as an array of messages.
     */
    private function formatPrompt(string|array $prompt): array
    {
        if (is_string($prompt)) {
            return [['role' => 'user', 'content' => $prompt]];
        }
        return $prompt;
    }

    /**
     * Set the HTTP client (for testing purposes).
     *
     * @param Client $client
     * @return self
     */
    public function setHttpClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

}