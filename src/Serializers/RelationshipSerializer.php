<?php

namespace Huntie\JsonApi\Serializers;

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
     * @param Model      $record   The primary record
     * @param string     $relation The named relation to serialize
     * @param array|null $fields   Subset of fields to return by record type
     */
    public function __construct($record, $relation, array $fields = [])
    {
        parent::__construct();

        $this->relation = $record->{$relation};
        $this->fields = array_unique($fields);
    }

    /**
     * Return a JSON API resource linkage representation, composed of a
     * resource identifier for each related record.
     *
     * @return Collection|Model|null
     */
    public function toResourceLinkage()
    {
        return $this->map(function($record) {
            return (new ResourceSerializer($record))->toResourceIdentifier();
        });
    }

    /**
     * Return a single, or collection of, JSON API resource objects for each
     * record in the relationship.
     *
     * @return Collection|Model|null
     */
    public function toResourceCollection()
    {
        return $this->map(function($record) {
            return (new ResourceSerializer($record, $this->fields))->toBaseResourceObject();
        });
    }

    /**
     * Return primary data for the JSON API document.
     *
     * @return array
     */
    protected function getPrimaryData()
    {
        return $this->toResourceLinkage()->toArray();
    }

    /**
     * Run a map over each item in the relationship.
     *
     * @param \Closure $callback
     *
     * @return Collection|Model|null
     */
    protected function map($callback)
    {
        if ($this->relation instanceof Collection) {
            return $this->relation->map($callback);
        } else if ($this->relation instanceof Model) {
            return $callback($this->relation);
        }

        return null;
    }
}
