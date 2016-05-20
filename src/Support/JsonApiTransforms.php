<?php

namespace Huntie\JsonApi\Support;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Transform Eloquent models and collections into JSON API objects.
 */
trait JsonApiTransforms
{
    /**
     * Transform a model instance into a JSON API object.
     *
     * @param \Illuminate\Database\Eloquent\Model $record
     * @param array|null                          $fields  Field subset to return
     * @param array|null                          $include Relations to include
     *
     * @return array
     */
    protected function transformRecord($record, array $fields = [], array $include = [])
    {
        $relations = array_unique(array_merge($record->getRelations(), $include));
        $record = $record->load($relations);

        $attributes = $record->toArray();
        $relationships = [];
        $included = [];

        foreach ($relations as $relation) {
            $relatedRecords = $record->{$relation};
            $relationships[$relation] = $this->transformCollectionIds($relatedRecords);

            if (in_array($relation, $include)) {
                $included = array_merge($included, $this->transformCollectionSimple($relatedRecords)['data']);
            }
        }

        array_forget($attributes, $relations);
        $included = array_filter($included);

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
     * @param \Illuminate\Database\Eloquent\Model $record
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
     * Transform a set of models into a JSON API collection.
     *
     * @param \Illuminate\Support\Collection|LengthAwarePaginator $records
     * @param array                                               $fields
     *
     * @return array
     */
    protected function transformCollection($records, array $fields = [])
    {
        $data = $records->map(function ($record) use ($fields) {
            return $this->transformRecord($record, $fields)['data'];
        })->toArray();

        $links = [];

        if ($records instanceof LengthAwarePaginator) {
            $links['first'] = $records->url(1);
            $links['last'] = $records->url($records->lastPage());
            $links['prev'] = $records->previousPageUrl();
            $links['next'] = $records->nextPageUrl();
        }

        return array_filter(compact('data', 'links'));
    }

    /**
     * Transform a set of models into a JSON API colleciton without additional data.
     *
     * @param \Illuminate\Support\Collection $records
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
     * @param \Illuminate\Support\Collection $records
     *
     * @return array
     */
    protected function transformCollectionIds($records)
    {
        $data = $records->map(function ($record) {
            return [
                'type' => $record->getTable(),
                'id' => $record->id,
            ];
        })->toArray();

        return compact('data');
    }
}
