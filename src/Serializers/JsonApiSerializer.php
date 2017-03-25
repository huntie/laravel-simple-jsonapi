<?php

namespace Huntie\JsonApi\Serializers;

use JsonSerializable;

abstract class JsonApiSerializer implements JsonSerializable
{
    /**
     * The JSON API version being implemented.
     *
     * @var string
     */
    const JSON_API_VERSION = '1.0';

    /**
     * Meta information to include.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Resource links to include.
     *
     * @var array
     */
    protected $links = [];

    /**
     * Return primary data for the JSON API document.
     *
     * @return mixed
     */
    abstract protected function getPrimaryData();

    /**
     * Return any secondary included resource objects.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getIncluded()
    {
        return collect();
    }

    /**
     * Add included meta information.
     *
     * @param string|array    $key
     * @param string|int|null $value
     */
    public function addMeta($key, $value = null)
    {
        $this->meta = array_merge($this->meta, is_array($key) ? $key : [$key => $value]);
    }

    /**
     * Add one or more included links.
     *
     * @param string|array    $key
     * @param string|int|null $value
     */
    public function addLinks($key, $value = null)
    {
        $this->links = array_merge($this->links, is_array($key) ? $key : [$key => $value]);
    }

    /**
     * Serialise JSON API document to an array.
     */
    public function serializeToObject(): array
    {
        return array_filter([
            'data' => $this->getPrimaryData(),
            'links' => $this->links,
            'meta' => $this->meta,
            'included' => $this->getIncluded()->toArray(),
            'jsonapi' => $this->getDocumentMeta(),
        ]);
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        return $this->serializeToObject();
    }

    /**
     * Serialise JSON API document to a JSON string.
     */
    public function serializeToJson(): string
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Return JSON API implementation information.
     */
    private function getDocumentMeta(): array
    {
        return array_filter([
            'version' => config('jsonapi.include_version') ? self::JSON_API_VERSION : null,
        ]);
    }
}
