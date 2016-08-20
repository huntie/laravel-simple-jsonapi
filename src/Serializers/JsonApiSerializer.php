<?php

namespace Huntie\JsonApi\Serializers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class JsonApiSerializer
{
    /**
     * The model instance to transform.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $record;

    /**
     * The relationships to return.
     *
     * @var array
     */
    protected $relationships;

    /**
     * The subset of record attributes to return.
     *
     * @var array
     */
    protected $fields;

    /**
     * The relationships to load and include.
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
     * Create a new JSON API resource serializer.
     *
     * @param Model      $record  The model instance to serialise
     * @param array|null $fields  Subset of fields to return
     * @param array|null $include Relations to include
     */
    public function __construct($record, array $fields = [], array $include = [])
    {
        $this->record = $record;
        $this->relationships = array_merge($record->getRelations(), $include);
        $this->fields = array_unique($fields);
        $this->include = array_unique($include);
        $this->meta = collect([]);
        $this->links = collect([]);
    }

    /**
     * Limit which relations can be included.
     *
     * @param array $include
     */
    public function scopeIncludes($include)
    {
        $this->include = array_intersect($this->include, $include);
    }

    /**
     * Add meta information to the returned object.
     *
     * @param string|array    $key
     * @param string|int|null $value
     */
    public function addMeta($key, $value = null)
    {
        $this->meta = $this->meta->merge(is_array($key) ? $key : [$key => $value]);
    }

    /**
     * Add one or more links to the returned object.
     *
     * @param string|array    $key
     * @param string|int|null $value
     */
    public function addLinks($key, $value = null)
    {
        $this->links = $this->links->merge(is_array($key) ? $key : [$key => $value]);
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
     * Return a JSON API resource object for the primary record.
     *
     * @return array
     */
    public function toResourceObject()
    {
        $this->record->load($this->relationships);

        return array_merge($this->toResourceIdentifier(), array_filter([
            'attributes' => $this->transformRecordAttributes(),
            'relationships' => $this->transformRecordRelations(),
        ]));
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
            'included' => $this->transformIncludedRelations(),
            'links' => $this->links->toArray(),
            'meta' => $this->meta->toArray(),
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

    /**
     * Return the attribute object data for the primary record.
     *
     * @return array
     */
    protected function transformRecordAttributes()
    {
        $attributes = array_diff_key($this->record->toArray(), $this->record->getRelations());
        $attributes = array_except($attributes, ['id']);

        if (!empty($this->fields)) {
            $attributes = array_only($attributes, $this->fields);
        }

        return $attributes;
    }

    /**
     * Return a collection of JSON API resource identifier objects by each
     * relation on the primary record.
     *
     * @return array
     */
    protected function transformRecordRelations()
    {
        $relationships = [];

        foreach ($this->relationships as $relation) {
            $relation = $this->record->{$relation};
            $data = [];

            if ($relation instanceof Collection) {
                $data = array_map(function ($record) {
                    return (new static($record))->toResourceIdentifier();
                }, $relation);
            } else if ($relation instanceof Model) {
                $data = (new static($relation))->toResourceIdentifier();
            }

            $relationships[$relation] = compact('data');
        }

        return $relationships;
    }

    /**
     * Return a collection of JSON API resource objects for each included
     * relationship.
     *
     * @return array
     */
    protected function transformIncludedRelations()
    {
        $included = collect([]);

        foreach ($this->include as $relation) {
            $included = $included->merge(array_map(function ($record) {
                return $record ? (new static($record))->toResourceObject() : null;
            }, (array) $this->record->{$relation}));
        }

        return array_filter($included->toArray());
    }
}
