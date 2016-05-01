<?php

namespace Huntie\JsonApi\Http\Controllers;

use Huntie\JsonApi\Support\JsonApiErrors;
use Huntie\JsonApi\Http\JsonApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
        $data = [];
        $records = $this->getModel()->all();

        foreach ($records as $record) {
            $data[] = $this->transformRecord($record);
        }

        return new JsonApiResponse(compact('data'));
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
        $data = $this->transformRecord($record);

        return new JsonApiResponse(compact('data'));
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
     * Transform a model instance into a JSON API object.
     *
     * @param Model      $record
     * @param array|null $include Relations to include
     * @param array|null $fields  Field names of attributes to limit to
     *
     * @return array
     */
    protected function transformRecord($record, array $include = [], array $fields = [])
    {
        $attributes = $record->toArray();

        if (!empty($fields)) {
            $attributes = array_only($attributes, $fields);
        }

        return [
            'type' => $record->getTable(),
            'id' => $record->id,
            'attributes' => array_except($attributes, ['id']),
        ];
    }
}
