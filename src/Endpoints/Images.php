<?php

declare(strict_types=1);

namespace GrokPHP\Endpoints;

use GrokPHP\Config;
use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;
use GrokPHP\Models\ChatMessage;
use GrokPHP\Params;
use GrokPHP\Traits\HasApiOperations;
use GrokPHP\Traits\ValidatesInput;
use GrokPHP\Utils\RequestBuilder;
use GrokPHP\Utils\ResponseParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Images
 * 
 * Handles all image understanding operations with the Grok AI API through chat completions.
 *
 * @package GrokPHP\Endpoints
 * @author Alvin Panford <panfordalvin@gmail.com>
 * @see https://docs.x.ai/docs/api-reference#chat-completions
 */
class Images
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
     * @var string The base endpoint for chat completions
     */
    private const CHAT_ENDPOINT = '/v1/chat/completions';

    /**
     * @var array Supported image formats
     */
    private const SUPPORTED_FORMATS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Images constructor.
     *
     * @param Config $config
     * @param Model|null
     */
    public function __construct(Config $config, ?Model $model = null)
    {
        $this->config = $config;
        
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
     * Analyze an image with optional text prompt using Grok's vision model.
     *
     * @param string $imageUrl URL of the image to analyze
     * @param string|null $prompt Optional text prompt for specific analysis
     * @param Params|null $params Additional params for analysis
     * @return ChatMessage
     * @throws GrokException
     */
    public function analyze(string $imageUrl, ?string $prompt = null, ?Params $params = null): ChatMessage
    {
        $this->validateImageUrl($imageUrl);

        $payload = $this->requestBuilder->buildImageAnalysisRequest(
            $imageUrl,
            $prompt,
            $params?->toArray() ?? [],
            $this->model->value
        );

        $response = $this->post(self::CHAT_ENDPOINT, $payload)[0];
        return $this->responseParser->parse($response, 'image');
    }

    /**
     * Validate image URL.
     *
     * @param string $imageUrl
     * @throws GrokException
     */
    private function validateImageUrl(string $imageUrl): void
    {
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new GrokException("Invalid image URL provided");
        }

        $extension = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
        if (!in_array($extension, self::SUPPORTED_FORMATS, true)) {
            throw new GrokException("Unsupported image format: {$extension}");
        }
    }
}
