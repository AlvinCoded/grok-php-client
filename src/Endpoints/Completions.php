<?php

declare(strict_types=1);

namespace GrokPHP\Endpoints;

use GrokPHP\Config;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatCompletion;
use GrokPHP\Traits\HasApiOperations;
use GrokPHP\Traits\ValidatesInput;
use GrokPHP\Utils\RequestBuilder;
use GrokPHP\Utils\ResponseParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Completions
 * 
 * Handles all text completion operations with the Grok AI API.
 *
 * @package GrokPHP\Endpoints
 * @author Alvin Panford <panfordalvin@gmail.com>
 * @see https://docs.x.ai/docs/api-reference#chat-completions
 */
class Completions
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
     * @var string The base endpoint for completions
     */
    private const COMPLETIONS_ENDPOINT = '/v1/completions';

    /**
     * Completions constructor.
     *
     * @param Client $client
     * @param Config $config
     */
    public function __construct(Client $client, Config $config)
    {
        $this->client = $client;
        $this->config = $config;
        $this->requestBuilder = new RequestBuilder();
        $this->responseParser = new ResponseParser();
    }

    /**
     * Create a completion for the provided prompt.
     *
     * @param string $prompt The prompt to complete
     * @param array $options Additional options for the completion request
     * @return ChatCompletion
     * @throws GrokException
     */
    public function create(string $prompt, array $options = []): ChatCompletion
    {
        $payload = $this->requestBuilder->buildCompletionRequest($prompt, $options);

        $response = $this->client->post(self::COMPLETIONS_ENDPOINT, [
            'json' => $payload,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->getApiKey(),
                'Content-Type' => 'application/json',
            ],
        ]);

        return $this->responseParser->parse($response, 'completion');
    }

    /**
     * Stream completions from the API.
     *
     * @param string $prompt The prompt to complete
     * @param callable $callback Function to handle each chunk of the stream
     * @param array $options Additional options for the completion request
     * @throws GrokException
     */
    public function stream(string $prompt, callable $callback, array $options = []): void
    {
        $payload = $this->requestBuilder->buildCompletionRequest($prompt, array_merge($options, ['stream' => true]));

        $this->stream(self::COMPLETIONS_ENDPOINT, $payload, ['callback' => function ($data) use ($callback) {
            $callback($this->responseParser->parseStreamChunk($data));
        }]);
    }

    /**
     * Create multiple completions for the same prompt.
     *
     * @param string $prompt The prompt to complete
     * @param int $n Number of completions to generate
     * @param array $options Additional options for the completion request
     * @return array
     * @throws GrokException
     */
    public function createMultiple(string $prompt, int $n = 3, array $options = []): array
    {
        if ($n < 1 || $n > 10) {
            throw new GrokException('Number of completions must be between 1 and 10');
        }

        $payload = $this->requestBuilder->buildCompletionRequest($prompt, array_merge($options, ['n' => $n]));

        $response = $this->post(self::COMPLETIONS_ENDPOINT, $payload);

        $result = json_decode($response[0]->getBody()->getContents(), false);
        return array_map(fn($choice) => new ChatCompletion(['choices' => [$choice]]), $result['choices']);
    }

    /**
     * Get token count for a given text.
     *
     * @param string $text The text to analyze
     * @return int
     * @throws GrokException
     */
    public function getTokenCount(string $text): int
    {
        $response = $this->post('/v1/tokenize', ['text' => $text]);

        $result = json_decode($response[0]->getBody()->getContents(), false);
        return $result['token_count'];
    }
}