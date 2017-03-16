<?php

namespace Huntie\JsonApi\Http\Controllers;

use Huntie\JsonApi\Contracts\Model\IncludesRelatedResources;
use Huntie\JsonApi\Exceptions\InvalidRelationPathException;
use Huntie\JsonApi\Http\JsonApiResponse;
use Huntie\JsonApi\Http\Concerns\QueriesResources;
use Huntie\JsonApi\Http\Concerns\UpdatesModelRelations;
use Huntie\JsonApi\Serializers\CollectionSerializer;
use Huntie\JsonApi\Serializers\RelationshipSerializer;
use Huntie\JsonApi\Serializers\ResourceSerializer;
use Huntie\JsonApi\Support\JsonApiErrors;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

abstract class JsonApiController extends Controller
{
    use JsonApiErrors;
    use QueriesResources;
    use UpdatesModelRelations;
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * The Eloquent Model for the resource.
     *
     * @var Model|string
     */
    protected $model;

    /**
     * Create a new JsonApiController instance.
     */
    public function __construct()
    {
        if (is_string($this->model)) {
            if (!is_subclass_of($this->model, Model::class)) {
                $this->model = str_finish(config('jsonapi.model_namespace', app()->getNamespace()), '\\')
                    . preg_replace('/Controller$/', '', class_basename($this));
            }

            $this->model = new $this->model;
        }
    }

    /**
     * Return a listing of the resource.
     *
     * @param \Huntie\JsonApi\Http\Requests\JsonApiRequest $request
     * @param \Illuminate\Database\Eloquent\Builder|null   $query   Custom resource query
     *
     * @return JsonApiResponse
     */
    public function indexAction($request, $query = null)
    {
        $records = $query ?: $this->model->newQuery();
        $this->validateIncludableRelations($request->inputSet('include'));

        $records = $this->sortQuery($records, $request->inputSet('sort'));
        $records = $this->filterQuery($records, (array) $request->input('filter'));

        try {
            $pageSize = min($this->model->getPerPage(), $request->input('page.size'));
            $pageNumber = $request->input('page.number') ?: 1;

            $records = $records->paginate($pageSize, null, 'page', $pageNumber);
        } catch (QueryException $e) {
            return $this->error(Response::HTTP_BAD_REQUEST, 'Invalid query parameters');
        }

        return new JsonApiResponse(new CollectionSerializer($records, $request->inputSet('fields'), $request->inputSet('include')));
    }

    /**
     * Store a new record.
     *
     * @param \Huntie\JsonApi\Http\Requests\JsonApiRequest $request
     *
     * @return JsonApiResponse
     */
    public function storeAction($request)
    {
        $record = $this->model->create((array) $request->input('data.attributes'));

        if ($relationships = $request->input('data.relationships')) {
            $this->updateResourceRelationships($record, (array) $relationships);
        }

        return new JsonApiResponse(new ResourceSerializer($record), Response::HTTP_CREATED);
    }

    /**
     * Return a specified record.
     *
     * @param \Huntie\JsonApi\Http\Requests\JsonApiRequest $request
     * @param Model|mixed                                  $record
     *
     * @return JsonApiResponse
     */
    public function showAction($request, $record)
    {
        $record = $this->findModelInstance($record);
        $this->validateIncludableRelations($request->inputSet('include'));

        return new JsonApiResponse(new ResourceSerializer($record, $request->inputSet('fields'), $request->inputSet('include')));
    }

    /**
     * Update a specified record.
     *
     * @param \Huntie\JsonApi\Http\Requests\JsonApiRequest $request
     * @param Model|mixed                                  $record
     *
     * @return JsonApiResponse
     */
    public function updateAction($request, $record)
    {
        $record = $this->findModelInstance($record);
        $record->fill((array) $request->input('data.attributes'));
        $record->save();

        if ($request->has('data.relationships')) {
            $this->updateResourceRelationships($record, (array) $request->input('data.relationships'));
        }

        return new JsonApiResponse(new ResourceSerializer($record));
    }

    /**
     * Destroy a specified record.
     *
     * @param \Huntie\JsonApi\Http\Requests\JsonApiRequest $request
     * @param Model|mixed                                  $record
     *
     * @return JsonApiResponse
     */
    public function destroyAction($request, $record)
    {
        $record = $this->findModelInstance($record);
        $record->delete();

        return new JsonApiResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return a specified record relationship.
     *
     * @param \Huntie\JsonApi\Http\Requests\JsonApiRequest $request
     * @param Model|mixed                                  $record
     * @param string                                       $relation
     *
     * @return JsonApiResponse
     */
    public function showRelationshipAction($request, $record, $relation)
    {
        $record = $this->findModelInstance($record);

        return new JsonApiResponse(new RelationshipSerializer($record, $relation));
    }

    /**
     * Update a named relationship on a specified record.
     *
     * http://jsonapi.org/format/#crud-updating-relationships
     *
     * @param \Huntie\JsonApi\Http\Requests\JsonApiRequest $request
     * @param Model|mixed                                  $record
     * @param string                                       $relation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return JsonApiResponse
     */
    public function updateRelationshipAction($request, $record, $relation)
    {
        $relationType = $this->getRelationType($relation);
        abort_unless(is_string($relationType) && $this->isFillableRelation($relation), Response::HTTP_NOT_FOUND);

        $record = $this->findModelInstance($record);
        $data = (array) $request->input('data');

        if ($relationType === 'To-One') {
            $this->updateToOneResourceRelationship($record, $relation, $data);
        } else if ($relationType === 'To-Many') {
            $this->updateToManyResourceRelationship($record, $relation, $data, $request->method());
        }

        return new JsonApiResponse(new RelationshipSerializer($record, $relation));
    }

    /**
     * Return existing instance of the resource or find by primary key.
     *
     * @param Model|mixed $record
     *
     * @throws ModelNotFoundException
     *
     * @return Model
     */
    protected function findModelInstance($record)
    {
        if ($record instanceof Model) {
            if (is_null($record->getKey())) {
                throw new ModelNotFoundException();
            }

            return $record;
        }

        return $this->model->findOrFail($record);
    }

    /**
     * Validate the requested included relationships against those that are
     * allowed on the requested resource type.
     *
     * @param array $relations
     *
     * @throws InvalidRelationPathException
     */
    protected function validateIncludableRelations(array $relations)
    {
        foreach ($relations as $relation) {
            if (!$this->model instanceof IncludesRelatedResources || !in_array($relation, $this->model->getIncludableRelations())) {
                throw new InvalidRelationPathException($relation);
            }
        }
    }
}
