<?php

namespace Huntie\JsonApi\Serializers;

use JsonSerializable;
use Request;

abstract class JsonApiSerializer implements JsonSerializable
{
    /**
     * The JSON API version being implemented.
     *
     * @var string
     */
    const JSON_API_VERSION = '1.0';

    /**
     * The base URL for links.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Meta information to include.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Resource links to include, relative to the base URL.
     *
     * @var array
     */
    protected $links = [];

    /**
     * Create a new JSON API document serializer.
     */
    public function __construct()
    {
        $this->baseUrl = Request::url();
        $this->addLinks('self', urldecode(str_replace(Request::url(), '', Request::fullUrl())));
    }

    /**
     * Return primary data for the JSON API document.
     *
     * @return mixed
     */
    abstract protected function getPrimaryData();

    /**
     * Return any links related to the primary data.
     */
    public function getLinks(): array
    {
        return array_map(function ($path) {
            return $this->baseUrl . $path;
        }, $this->links);
    }

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
     * Set the base URL for document links.
     *
     * @param string $url
     */
    public function setBaseUrl(string $url)
    {
        $this->baseUrl = preg_replace('/\/$/', '', $url);
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
        return array_merge(
            ['data' => $this->getPrimaryData()],
            array_filter([
                'included' => $this->getIncluded()->values()->toArray(),
                'links' => $this->getLinks(),
                'meta' => $this->meta,
                'jsonapi' => $this->getDocumentMeta(),
            ])
        );
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
