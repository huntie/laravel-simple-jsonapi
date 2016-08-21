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
     * Return a base JSON API resource object for the primary record containing
     * only immediate attributes.
     *
     * @return array
     */
    public function toBaseResourceObject()
    {
        return array_merge($this->toResourceIdentifier(), [
            'attributes' => $this->transformRecordAttributes(),
        ]);
    }

    /**
     * Return a full JSON API resource object for the primary record.
     *
     * @return array
     */
    public function toResourceObject()
    {
        $this->record->load($this->relationships);

        return array_merge($this->toBaseResourceObject(), [
            'relationships' => $this->transformRecordRelations()->toArray(),
        ]);
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
            'included' => $this->transformIncludedRelations()->toArray(),
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
        $modelName = collect(explode('\\', get_class($this->record)))->last();

        return snake_case(str_plural($modelName), '-');
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
     * @return \Illuminate\Support\Collection
     */
    protected function transformRecordRelations()
    {
        $relationships = collect([]);

        foreach ($this->relationships as $relation) {
            $data = $this->mapRelation($relation, function ($record) {
                return (new static($record))->toResourceIdentifier();
            });

            $relationships = $relationships->merge([$relation => compact('data')]);
        }

        return $relationships;
    }

    /**
     * Return a collection of JSON API resource objects for each included
     * relationship.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function transformIncludedRelations()
    {
        $included = collect([]);

        foreach ($this->include as $relation) {
            $records = $this->mapRelation($relation, function ($record) {
                return (new static($record))->toBaseResourceObject();
            });

            $included = $included->merge(collect($records));
        }

        return $included;
    }

    /**
     * Run a map over each item in a loaded relation on the primary record.
     *
     * @param string   $relation
     * @param \Closure $callback
     *
     * @return Collection|Model|null
     */
    protected function mapRelation($relation, $callback)
    {
        $loadedRelation = $this->record->{$relation};

        if ($loadedRelation instanceof Collection) {
            return $loadedRelation->map($callback);
        } else if ($loadedRelation instanceof Model) {
            return $callback($loadedRelation);
        }

        return null;
    }
}
