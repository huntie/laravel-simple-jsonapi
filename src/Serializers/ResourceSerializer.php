<?php

namespace Huntie\JsonApi\Serializers;

use Illuminate\Support\Collection;

class ResourceSerializer extends JsonApiSerializer
{
    /**
     * The model instance to transform.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $record;

    /**
     * The record relationships to return.
     *
     * @var array
     */
    protected $relationships;

    /**
     * The subset of attributes to return on each record type.
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
     * Create a new JSON API resource serializer.
     *
     * @param \Illuminate\Database\Eloquent\Model $record  The model instance to serialise
     * @param array|null                          $fields  Subset of fields to return by record type
     * @param array|null                          $include Relations to include
     */
    public function __construct($record, array $fields = [], array $include = [])
    {
        parent::__construct();

        $this->record = $record;
        $this->relationships = array_merge(array_keys($record->getRelations()), $include);
        $this->fields = array_unique($fields);
        $this->include = array_unique($include);
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
     * Return a JSON API resource identifier object for the primary record.
     *
     * @return array
     */
    public function toResourceIdentifier()
    {
        return [
            'type' => $this->getResourceType(),
            'id' => $this->getPrimaryKey(),
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
        return array_filter(array_merge($this->toBaseResourceObject(), [
            'relationships' => $this->transformRecordRelations()->toArray(),
        ]));
    }

    /**
     * Return a collection of JSON API resource objects for each included
     * relationship.
     *
     * @throws \Huntie\JsonApi\Exceptions\InvalidRelationPathException
     *
     * @return \Illuminate\Support\Collection
     */
    public function getIncludedRecords()
    {
        return collect($this->include)->map(function ($relation) {
            $records = (new RelationshipSerializer($this->record, $relation, $this->fields))->toResourceCollection();

            return $records instanceof Collection ? $records : [$records];
        })->flatten(1)->unique()->values();
    }

    /**
     * Return primary data for the JSON API document.
     *
     * @return mixed
     */
    protected function getPrimaryData()
    {
        return $this->toResourceObject();
    }

    /**
     * Return any secondary included resource data.
     *
     * @return array
     */
    protected function getIncludedData()
    {
        return $this->getIncludedRecords()->toArray();
    }

    /**
     * Return the primary resource type name.
     *
     * @return string
     */
    protected function getResourceType()
    {
        $modelName = collect(explode('\\', get_class($this->record)))->last();

        return snake_case(str_plural($modelName), '-');
    }

    /**
     * Return the primary key value for the resource.
     *
     * @return int|string
     */
    protected function getPrimaryKey()
    {
        $value = $this->record->getKey();

        return is_int($value) ? $value : (string) $value;
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
        $fields = array_get($this->fields, $this->getResourceType());

        if (!empty($fields)) {
            $attributes = array_only($attributes, $fields);
        }

        return $attributes;
    }

    /**
     * Return a collection of JSON API resource identifier objects by each
     * relation on the primary record.
     *
     * @throws \Huntie\JsonApi\Exceptions\InvalidRelationPathException
     *
     * @return \Illuminate\Support\Collection
     */
    protected function transformRecordRelations()
    {
        return collect($this->relationships)->combine(array_map(function ($relation) {
            return [
                'data' => (new RelationshipSerializer($this->record, $relation))->toResourceLinkage(),
            ];
        }, $this->relationships));
    }
}
