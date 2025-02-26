<?php

declare(strict_types=1);

namespace GrokPHP\Models;

use GrokPHP\Enums\Model;
use GrokPHP\Exceptions\GrokException;
use JsonSerializable;

/**
 * Class EmbeddingResponse
 * 
 * Handles the response from embedding capabilities through the embeddings endpoint.
 *
 * @package GrokPHP\Models.
 * @see https://docs.x.ai/docs/api-reference#create-embeddings
 */
class EmbeddingResponse implements JsonSerializable
{
    /**
     * @var string The object type
     */
    private string $object;

    /**
     * @var array The data array
     */
    private array $data;

    /**
     * @var Model The model used for embedding
     */
    private Model $model;

    /**
     * @var array Token usage statistics
     */
    private array $usage;

    /**
     * EmbeddingResponse constructor.
     *
     * @param array $data
     * @throws GrokException
     */
    public function __construct(array $data)
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new GrokException('Invalid embedding response format');
        }

        $this->object = $data['object'] ?? 'list';
        $this->data = $data['data'];
        $this->model = Model::fromString($data['model'] ?? '');
        $this->usage = $data['usage'] ?? [];
    }

    /**
     * Returns an array of embeddings from the data property.
     *
     * @return array
     */
    public function getEmbeddings(): array
    {
        return array_map(fn($item) => $item['embedding'] ?? [], $this->data);
    }

    /**
     * Get the model instance associated with this embedding response
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Gets the token usage information for this embedding request.
     * 
     * @return array
     */
    public function getUsage(): array
    {
        return $this->usage;
    }

    /**
     * Serializes the embedding response to a JSON-serializable array.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'object' => $this->object,
            'data' => $this->data,
            'model' => $this->model->value,
            'usage' => $this->usage,
        ];
    }
}
