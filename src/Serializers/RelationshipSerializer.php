<?php

namespace Huntie\JsonApi\Serializers;

use Huntie\JsonApi\Exceptions\InvalidRelationPathException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RelationshipSerializer extends JsonApiSerializer
{
    /**
     * The resolved relation to transform.
     *
     * @var Collection|Model|null
     */
    protected $relation;

    /**
     * The subset of attributes to return on each resource type.
     *
     * @var array
     */
    protected $fields;

    /**
     * The named relationships to list on the resolved resource(s).
     *
     * @var array
     */
    protected $relationships;

    /**
     * Create a new JSON API relationship serializer.
     *
     * @param Model      $record        The primary record
     * @param string     $relation      The name of the relation to serialize
     * @param array|null $fields        The subset of fields to return on each resource type
     * @param array|null $relationships The named relationships to list on the resolved resource(s)
     *
     * @throws InvalidRelationPathException
     */
    public function __construct($record, $relation, array $fields = [], array $relationships = [])
    {
        parent::__construct();

        if (in_array($relation, $record->getHidden())) {
            throw new InvalidRelationPathException($relation);
        }

        $this->relation = $record->{$relation};
        $this->fields = array_unique($fields);
        $this->relationships = array_unique($relationships);
    }

    /**
     * Return a JSON API resource linkage representation, composed of a
     * resource identifier for each related record.
     *
     * @return Collection|array|null
     */
    public function toResourceLinkage()
    {
        return $this->map(function ($record) {
            return (new ResourceSerializer($record))->toResourceIdentifier();
        });
    }

    /**
     * Return a single, or collection of, JSON API resource objects for each
     * record in the relationship.
     *
     * @return Collection|array|null
     */
    public function toResourceCollection()
    {
        return $this->map(function ($record) {
            return (new ResourceSerializer($record, $this->fields))->toBaseResourceObject();
        });
    }

    /**
     * Return primary data for the JSON API document.
     *
     * @return mixed
     */
    protected function getPrimaryData()
    {
        return $this->toResourceLinkage();
    }

    /**
     * Run a map over each item in the relationship.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    protected function map(callable $callback)
    {
        if ($this->relation instanceof Collection) {
            return $this->relation->map($callback);
        } else if ($this->relation instanceof Model) {
            return call_user_func($callback, $this->relation);
        }

        return null;
    }
}
