<?php

namespace Huntie\JsonApi\Http\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Specify and update relationships on the primary controller resource.
 *
 * @property $model
 */
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
    protected function isFillableRelation(string $relation): bool
    {
        return array_key_exists($relation, $this->fillableRelations);
    }

    /**
     * Determine the JSON API relation type for a named relation on the primary
     * resource.
     *
     * @param string $relation
     *
     * @return string|void
     */
    protected function getRelationType(string $relation)
    {
        $relation = $this->model->{$relation}();

        if ($relation instanceof BelongsTo) {
            return 'To-One';
        } else if ($relation instanceof BelongsToMany) {
            return 'To-Many';
        }
    }

    /**
     * Update a named many-to-one relationship association on a model instance.
     *
     * http://jsonapi.org/format/#crud-updating-to-one-relationships
     *
     * @param Model  $record
     * @param string $relation
     * @param array  $data
     */
    protected function updateToOneResourceRelationship($record, string $relation, array $data)
    {
        $record->{$relation}()->associate($data['id']);
        $record->save();
    }

    /**
     * Update named many-to-many relationship entries on a model instance.
     *
     * http://jsonapi.org/format/#crud-updating-to-many-relationships
     *
     * @param Model  $record
     * @param string $relation
     * @param array  $data
     * @param string $method
     */
    protected function updateToManyResourceRelationship($record, string $relation, array $data, string $method)
    {
        $items = [];

        foreach ($data as $item) {
            if (isset($item['attributes'])) {
                $items[$item['id']] = $item['attributes'];
            } else {
                $items[] = $item['id'];
            }
        }

        switch ($method) {
            case 'PATCH':
                $record->{$relation}()->sync($items);
                break;
            case 'POST':
                $record->{$relation}()->sync($items, false);
                break;
            case 'DELETE':
                $record->{$relation}()->detach(array_keys($items));
        }
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
            if ($this->getRelationType($name) === 'To-One') {
                $record->{$name}()->associate(array_get($relationship, 'data.id'));
                $record->save();
            } else if ($this->getRelationType($relation) === 'To-Many') {
                $record->{$name}()->sync(array_pluck($relationship['data'], 'id'));
            }
        }
    }
}
