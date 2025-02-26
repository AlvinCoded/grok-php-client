<?php

declare(strict_types=1);

namespace GrokPHP\Endpoints;

use GrokPHP\Config;
use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatCompletion;
use GrokPHP\Params;
use GrokPHP\Traits\HasApiOperations;
use GrokPHP\Traits\ValidatesInput;
use GrokPHP\Utils\RequestBuilder;
use GrokPHP\Utils\ResponseParser;
use GuzzleHttp\Client;

/**
 * Class Completions
 * 
 * Handles all text completion operations with the Grok AI API.
 *
 * @package GrokPHP\Endpoints.
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
     * @var Model The model instance
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
     * @var string The base endpoint for completions
     */
    private const COMPLETIONS_ENDPOINT = '/v1/completions';

    /**
     * Completions constructor.
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
     * Create a completion for the provided prompt.
     *
     * @param string $prompt The prompt to complete
     * @param Params|null $params Additional parameters for the completion request
     * @return ChatCompletion
     * @throws GrokException
     */
    public function create(string $prompt, ?Params $params = null): ChatCompletion
    {
        $payload = $this->requestBuilder->buildCompletionRequest(
            $prompt,
            $params?->toArray() ?? [],
            $this->model->value
        );

        $response = $this->post(self::COMPLETIONS_ENDPOINT, $payload)[0];
        return $this->responseParser->parse($response, 'completion');
    }

    /**
     * Stream completions from the API.
     *
     * @param string $prompt The prompt to complete
     * @param callable $callback Function to handle each chunk of the stream
     * @param Params|null $params Additional parameters for the completion request
     * @throws GrokException
     */
    public function stream(string $prompt, callable $callback, ?Params $params = null): void
    {
        $payload = $this->requestBuilder->buildCompletionRequest(
            $prompt,
            ($params ?? Params::create()->stream())->toArray(),
            $this->model->value
        );

        $this->streamRequest(self::COMPLETIONS_ENDPOINT, $payload, $callback);

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

        foreach ($response->getBody() as $chunk) {
            $callback($chunk);
        }
    }

    /**
     * Create multiple completions for the same prompt.
     *
     * @param string $prompt The prompt to complete
     * @param int $n Number of completions to generate
     * @param Params|null $params Additional parameters for the completion request
     * @return array
     * @throws GrokException
     */
    public function createMultiple(string $prompt, int $n = 3, ?Params $params = null): array
    {
        if ($n < 1 || $n > 10) {
            throw new GrokException('Number of completions must be between 1 and 10');
        }

        $payload = $this->requestBuilder->buildCompletionRequest(
            $prompt,
            ($params ?? Params::create()->n($n))->toArray(),
            $this->model->value
        );

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