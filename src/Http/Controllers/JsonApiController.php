<?php

namespace Huntie\JsonApi\Http\Controllers;

use Huntie\JsonApi\Http\JsonApiResponse;
use Huntie\JsonApi\Support\JsonApiErrors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

abstract class JsonApiController extends Controller
{
    use JsonApiErrors;

    /**
     * Return the Eloquent Model for the resource.
     *
     * @return Model
     */
    abstract protected function getModel();

    /**
     * Return the type name of the resource.
     *
     * @return string
     */
    protected function getModelType()
    {
        return $this->getModel()->getTable();
    }

    /**
     * Return a listing of the resource.
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function indexAction(Request $request)
    {
        $records = $this->getModel()->all();
        $params = $this->getRequestParameters($request);

        return new JsonApiResponse($this->transformCollection($records, $params['fields']));
    }

    /**
     * Store a new record.
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function storeAction(Request $request)
    {
        $record = $this->getModel()->create((array) $request->input('data.attributes'));

        return new JsonApiResponse($this->transformRecord($record), Response::HTTP_CREATED);
    }

    /**
     * Return a specified record.
     *
     * @param Request   $request
     * @param Model|int $record
     *
     * @return JsonApiResponse
     */
    public function showAction(Request $request, $record)
    {
        $record = $record instanceof Model ? $record : $this->findModelInstance($record);
        $params = $this->getRequestParameters($request);

        return new JsonApiResponse($this->transformRecord($record, $params['fields'], $params['include']));
    }

    /**
     * Update a specified record.
     *
     * @param Request   $request
     * @param Model|int $record
     *
     * @return JsonApiResponse
     */
    public function updateAction(Request $request, $record)
    {
        $record = $record instanceof Model ? $record : $this->findModelInstance($record);
        $record->update((array) $request->input('data.attributes'));

        return $this->showAction($request, $record);
    }

    /**
     * Destroy a specified record.
     *
     * @param Request   $request
     * @param Model|int $record
     *
     * @return JsonApiResponse
     */
    public function destroyAction(Request $request, $record)
    {
        $record = $record instanceof Model ? $record : $this->findModelInstance($record);
        $record->delete();

        return new JsonApiResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return an instance of the resource by primary key.
     *
     * @param int $key
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     *
     * @return Model
     */
    protected function findModelInstance($key)
    {
        return $this->getModel()->findOrFail($key);
    }

    /**
     * Return any JSON API resource parameters from a request.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestParameters($request)
    {
        return [
            'fields' => $this->getRequestQuerySet($request, 'fields.' . $this->getModelType()),
            'include' => $this->getRequestQuerySet($request, 'include'),
        ];
    }

    /**
     * Return any comma separated values in a request query field as an array.
     *
     * @param Request $request
     * @param string  $key
     *
     * @return array
     */
    protected function getRequestQuerySet($request, $key)
    {
        return preg_split('/,/', $request->input($key), null, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Transform a set of models into a JSON API collection.
     *
     * @param \Illuminate\Support\Collection $records
     * @param array                          $fields
     *
     * @return array
     */
    protected function transformCollection($records, array $fields = [])
    {
        $data = [];

        foreach ($records as $record) {
            $data[] = $this->transformRecord($record, $fields)['data'];
        }

        return compact('data');
    }

    /**
     * Transform a model instance into a JSON API object.
     *
     * @param Model      $record
     * @param array|null $fields  Field names of attributes to limit to
     * @param array|null $include Relations to include
     *
     * @return array
     */
    protected function transformRecord($record, array $fields = [], array $include = [])
    {
        $relations = array_unique(array_merge($record->getRelations(), $include));
        $attributes = $record->load($relations)->toArray();
        $relationships = [];
        $included = [];

        foreach ($relations as $relation) {
            $relationships[$relation] = [
                'data' => []
            ];

            foreach (array_pull($attributes, $relation) as $relatedRecord) {
                $relationships[$relation]['data'][] = [
                    'type' => $relation,
                    'id' => $relatedRecord['id'],
                ];

                if (in_array($relation, $include)) {
                    $included[] = [
                        'type' => $relation,
                        'id' => $relatedRecord['id'],
                        'attributes' => array_except($relatedRecord, ['id', 'pivot']),
                    ];
                }
            }
        }

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
}
