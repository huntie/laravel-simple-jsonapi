<?php

namespace Huntie\JsonApi\Serializers;

use Huntie\JsonApi\Support\RelationshipIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RelationshipSerializer extends JsonApiSerializer
{
    /**
     * The loaded relation to transform.
     *
     * @var Collection|Model|null
     */
    protected $relation;

    /**
     * The subset of attributes to return on each included record type.
     *
     * @var array
     */
    protected $fields;

    /**
     * Create a new JSON API relationship serializer.
     *
     * @param Model      $record The primary record
     * @param string     $path   The path to the relation to serialize
     * @param array|null $fields Subset of fields to return by record type
     *
     * @throws InvalidRelationPathException
     */
    public function __construct($record, $path, array $fields = [])
    {
        parent::__construct();

        $this->relation = (new RelationshipIterator($record, $path))->resolve();
        $this->fields = array_unique($fields);
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
