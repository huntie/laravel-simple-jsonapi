<?php

namespace Huntie\JsonApi\Http\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait UpdatesModelRelations
{
    /**
     * The model relationships that can be updated.
     *
     * @var array
     */
    protected $fillableRelations = [];

    /**
     * Determine whether the given named relation can be updated on the model.
     *
     * @param string $relation
     */
    protected function isFillableRelation($relation): bool
    {
        return array_key_exists($relation, $this->fillableRelations);
    }

    /**
     * Update one or more relationships on a model instance from an array of
     * named relationships and associated resource identifiers.
     *
     * http://jsonapi.org/format/#crud-updating-resource-relationships
     *
     * @param Model $record
     * @param array $relationships
     */
    protected function updateResourceRelationships($record, array $relationships)
    {
        $relationships = array_intersect_key($relationships, array_flip($this->fillableRelations));

        foreach ($relationships as $name => $relationship) {
            $relation = $record->{$name}();

            if ($relation instanceof BelongsTo) {
                $record->{$name}()->associate(array_get($relationship, 'data.id'));
                $record->save();
            } else if ($relation instanceof BelongsToMany) {
                $record->{$name}()->sync(array_pluck($relationship['data'], 'id'));
            }
        }
    }
}
