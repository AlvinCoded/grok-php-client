<?php

declare(strict_types=1);

namespace GrokPHP\Endpoints;

use GrokPHP\Config;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Traits\HasApiOperations;
use GrokPHP\Traits\ValidatesInput;
use GrokPHP\Utils\RequestBuilder;
use GrokPHP\Utils\ResponseParser;
use GuzzleHttp\Client;

/**
 * Class Chat
 * 
 * Handles all chat-related operations with the Grok AI API.
 *
 * @package GrokPHP\Endpoints
 * @author Alvin Panford <panfordalvin@gmail.com>
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
     * Chat constructor.
     *
     * @param Client $client
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
     * @param string $message The message content
     * @param array $options Additional options for the request
     * @return ChatMessage
     * @throws GrokException
     */
    public function send(string $message, array $options = []): ChatMessage
    {
        $payload = $this->requestBuilder->buildChatRequest(
            [['role' => 'user', 'content' => $message]],
            $options
        );

        $headers = $this->requestBuilder->buildHeaders($this->config->getApiKey());
        $response = $this->client->post(self::CHAT_ENDPOINT, [
            'json' => $payload,
            'headers' => $headers,
        ]);

        return $this->responseParser->parse($response, 'chat');
    }

    /**
     * Create a chat conversation with multiple messages.
     *
     * @param array $messages Array of message objects with role and content
     * @param array $options Additional options for the request
     * @return ChatMessage
     * @throws GrokException
     */
    public function conversation(array $messages, array $options = []): ChatMessage
    {
        $payload = $this->requestBuilder->buildChatRequest($messages, $options);

        $response = $this->client->post(self::CHAT_ENDPOINT, ['json' => $payload]);

        return $this->responseParser->parse($response, 'chat');
    }

    /**
     * Stream a chat response from Grok AI.
     *
     * @param string $message The message content
     * @param callable $callback Function to handle each chunk of the stream
     * @param array $options Additional options for the request
     * @throws GrokException
     */
    public function streamChat(string $message, callable $callback, array $options = []): void
    {
        $payload = $this->requestBuilder->buildChatRequest(
            [['role' => 'user', 'content' => $message]],
            array_merge($options, ['stream' => true])
        );

        $this->stream(self::CHAT_ENDPOINT, $payload, array_merge($options, [
            'callback' => function ($data) use ($callback) {
                $callback($this->responseParser->parseStreamChunk($data));
            }
        ]));
    }
}