<?php

namespace Huntie\JsonApi\Serializers;

class JsonApiSerializer
{
    /**
     * The model instance to transform.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $record;

    /**
     * The subset of record attributes to return.
     *
     * @var array
     */
    protected $fields;

    /**
     * The record relations to include.
     *
     * @var array
     */
    protected $include;

    /**
     * Meta information to include.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $meta;

    /**
     * Resource links to include.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $links;

    /**
     * The loaded records to include.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $included;

    /**
     * Create a new JSON API resource serializer.
     *
     * @param Model      $record  The model instance to serialise
     * @param array|null $fields  Subset of fields to return
     * @param array|null $include Relations to include
     */
    public function __construct($record, array $fields = [], array $include = [])
    {
        $this->record = $record;
        $this->fields = array_unique($fields);
        $this->include = array_unique(array_merge($record->getRelations(), $include));
        $this->meta = collect([]);
        $this->links = collect([]);
        $this->included = collect([]);
    }

    /**
     * Limit which relations can be included.
     *
     * @param array $include
     */
    public function scopeIncludes($include)
    {
        $this->include->intersect($include);
    }

    /**
     * Add meta information to the returned object.
     *
     * @param string|array    $key
     * @param string|int|null $value
     */
    public function addMeta($key, $value = null)
    {
        $this->meta->merge(is_array($key) ? $key : [$key => $value]);
    }

    /**
     * Add one or more links to the returned object.
     *
     * @param string|array    $key
     * @param string|int|null $value
     */
    public function addLinks($key, $value = null)
    {
        $this->links->merge(is_array($key) ? $key : [$key => $value]);
    }

    /**
     * Return a JSON API resource object for the primary record.
     *
     * @return array
     */
    public function toResourceObject()
    {
        return array_merge($this->toResourceIdentifier(), [
            // TODO
        ]);
    }

    /**
     * Return a JSON API resource identifier object for the primary record.
     *
     * @return array
     */
    public function toResourceIdentifier()
    {
        return [
            'type' => $this->getRecordType(),
            'id' => $this->record->id,
        ];
    }

    /**
     * Serialise complete JSON API document to an array.
     *
     * @return array
     */
    public function serialiseToObject()
    {
        return array_filter([
            'data' => $this->toResourceObject(),
            'included' => $this->included->isEmpty() ? null : $this->included->toArray(),
            'links' => $this->links->isEmpty() ? null : $this->links->toArray(),
            'meta' => $this->meta->isEmpty() ? null : $this->meta->toArray(),
        ]);
    }

    /**
     * Serialise complete JSON API document to a JSON string.
     *
     * @return array
     */
    public function serializeToJson()
    {
        return json_encode($this->serialiseToObject());
    }

    /**
     * Return the primary record type name.
     *
     * @return string
     */
    protected function getRecordType()
    {
        return str_slug(str_plural(get_class($this->record)));
    }
}
