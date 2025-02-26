<?php

declare(strict_types=1);

namespace GrokPHP\Endpoints;

use GrokPHP\Config;
use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\EmbeddingResponse;
use GrokPHP\Params;
use GrokPHP\Traits\HasApiOperations;
use GrokPHP\Traits\ValidatesInput;
use GrokPHP\Utils\RequestBuilder;
use GrokPHP\Utils\ResponseParser;
use GuzzleHttp\Client;

/**
 * Class Embeddings
 * 
 * Represents the Embeddings endpoint for creating vector representations of text.
 * This class handles embedding generation requests to the AI model.
 * 
 * @package GrokPHP\Endpoints.
 * @see https://docs.x.ai/docs/api-reference#create-embeddings
 */
class Embeddings
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
     * @var string The base endpoint for embeddings
     */
    private const EMBEDDINGS_ENDPOINT = '/v1/embeddings';

    /**
     * Embeddings constructor.
     *
     * @param Config $config
     * @param Model|null $model
     * @throws \InvalidArgumentException
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
     * Creates embeddings for the given input using the specified model.
     * 
     * @param string|array $input
     * @param Params|null $params
     * @return EmbeddingResponse
     * 
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(string|array $input, ?Params $params = null): EmbeddingResponse
    {
        $payload = $this->requestBuilder->buildEmbeddingRequest(
            $input,
            $params?->toArray() ?? [],
            $this->model->value
        );

        $response = $this->client->post(self::EMBEDDINGS_ENDPOINT, [
            'json' => $payload,
            'headers' => $this->requestBuilder->buildHeaders($this->config->getApiKey()),
        ]);

        return $this->responseParser->parse($response, 'embedding');
    }
}
