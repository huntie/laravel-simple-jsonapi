<?php

namespace Huntie\JsonApi\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Transform Eloquent models and collections into JSON API objects.
 */
trait JsonApiTransforms
{
    /**
     * Transform a model instance into a JSON API object.
     *
     * @param Model      $record
     * @param array|null $fields  Field subset to return
     * @param array|null $include Relations to include
     *
     * @return array
     */
    protected function transformRecord($record, array $fields = [], array $include = [])
    {
        $relations = array_unique(array_merge($record->getRelations(), $include));
        $record = $record->load($relations);

        $attributes = $record->toArray();
        $relationships = [];
        $included = collect([]);

        foreach ($relations as $relation) {
            $relationships[$relation] = $this->transformRelationship($record, $relation);

            if (in_array($relation, $include)) {
                if ($record->{$relation} instanceof Collection) {
                    $included->merge($this->transformCollectionSimple($record->{$relation})['data']);
                } else if ($record->{$relation} instanceof Model) {
                    $included->push($this->transformRecordSimple($record->{$relation})['data']);
                }
            }
        }

        array_forget($attributes, $relations);
        $included = array_filter($included->toArray());

        if (!empty($fields)) {
            $attributes = array_only($attributes, $fields);
        }

        $data = array_filter([
            'type' => $record->getTable(),
            'id' => $record->id,
            'attributes' => array_except($attributes, ['id']),
            'relationships' => $relationships,
        ]);

        return array_filter(compact('data', 'included'));
    }

    /**
     * Transform a model instance into a JSON API object without additonal data.
     *
     * @param Model $record
     *
     * @return array
     */
    protected function transformRecordSimple($record)
    {
        $attributes = array_diff_key($record->toArray(), $record->getRelations());
        $attributes = array_except($attributes, ['id']);

        return [
            'data' => [
                'type' => $record->getTable(),
                'id' => $record->id,
                'attributes' => $attributes,
            ]
        ];
    }

    /**
     * Transform a model instance into a JSON API resource identifier.
     *
     * @param Model $record
     *
     * @return array
     */
    protected function transformRecordIdentifier($record)
    {
        return [
            'data' => [
                'type' => $record->getTable(),
                'id' => $record->id,
            ]
        ];
    }

    /**
     * Transform a set of models into a JSON API collection.
     *
     * @param Collection|LengthAwarePaginator $records
     * @param array                           $fields
     * @param array|null                      $include
     *
     * @return array
     */
    protected function transformCollection($records, array $fields = [], array $include = [])
    {
        $data = [];
        $links = [];
        $included = [];

        foreach ($records as $record) {
            $object = $this->transformRecord($record, $fields, $include);

            if (isset($object['included'])) {
                $included = array_merge($included, $object['included']);
            }

            $data[] = $object['data'];
        }

        if ($records instanceof LengthAwarePaginator) {
            $links['first'] = $records->url(1);
            $links['last'] = $records->url($records->lastPage());
            $links['prev'] = $records->previousPageUrl();
            $links['next'] = $records->nextPageUrl();
        }

        return array_merge(compact('data'), array_filter(compact('links', 'included')));
    }

    /**
     * Transform a set of models into a JSON API collection without additional data.
     *
     * @param Collection $records
     *
     * @return array
     */
    protected function transformCollectionSimple($records)
    {
        $data = $records->map(function ($record) {
            return $this->transformRecordSimple($record)['data'];
        })->toArray();

        return compact('data');
    }

    /**
     * Transform a set of models into a collection of JSON API resource
     * identifier objects.
     *
     * @param Collection $records
     *
     * @return array
     */
    protected function transformCollectionIdentifiers($records)
    {
        $data = $records->map(function ($record) {
            return $this->transformRecordIdentifier($record)['data'];
        });

        return compact('data');
    }

    /**
     * Transform a model relationship into a single, or collection of, JSON API
     * resource identifier objects.
     *
     * @param Model  $record
     * @param string $relation
     *
     * @return array
     */
    protected function transformRelationship($record, $relation)
    {
        $data = null;

        if ($record->{$relation} instanceof Collection) {
            return $this->transformCollectionIdentifiers($record->{$relation});
        } else if ($record->{$relation} instanceof Model) {
            return $this->transformRecordIdentifier($record->{$relation});
        }

        return compact('data');
    }
}
