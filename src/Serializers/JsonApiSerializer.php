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
     *
     * @return array
     */
    public function serializeToObject()
    {
        return array_filter([
            'data' => $this->getPrimaryData(),
            'links' => $this->links,
            'meta' => $this->meta,
            'included' => array_filter($this->getIncludedData()),
            'jsonapi' => $this->getDocumentMeta(),
        ]);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->serializeToObject();
    }

    /**
     * Serialise JSON API document to a JSON string.
     *
     * @return array
     */
    public function serializeToJson()
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Return any secondary included resource data.
     *
     * @return array
     */
    protected function getIncludedData()
    {
        return [];
    }

    /**
     * Return JSON API implementation information.
     *
     * @return array
     */
    private function getDocumentMeta()
    {
        return array_filter([
            'version' => config('jsonapi.include_version') ? self::JSON_API_VERSION : null,
        ]);
    }
}
