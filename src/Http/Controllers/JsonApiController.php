<?php

namespace Huntie\JsonApi\Http\Controllers;

use Validator;
use Huntie\JsonApi\Contracts\Model\IncludesRelatedResources;
use Huntie\JsonApi\Exceptions\HttpException;
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
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

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
     * @param Request                                    $request
     * @param \Illuminate\Database\Eloquent\Builder|null $query   Custom resource query
     *
     * @return JsonApiResponse
     */
    public function indexAction(Request $request, $query = null)
    {
        $records = $query ?: $this->model->newQuery();
        $params = $this->getRequestParameters($request);
        $this->validateIncludableRelations($params['include']);

        try {
            $records = $this->sortQuery($records, $params['sort']);
            $records = $this->filterQuery($records, $params['filter']);
            $page = $this->resolvePaginationParameters($request);

            $records = $records->paginate($page['size'], null, null, $page['number']);
        } catch (QueryException $e) {
            return $this->error(Response::HTTP_BAD_REQUEST, 'Invalid query parameters');
        }

        return new JsonApiResponse(new CollectionSerializer($records, $params['fields'], $params['include']));
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
        $record = $this->model->create((array) $request->input('data.attributes'));

        if ($relationships = $request->input('data.relationships')) {
            $this->updateResourceRelationships($record, (array) $relationships);
        }

        return new JsonApiResponse(new ResourceSerializer($record), Response::HTTP_CREATED);
    }

    /**
     * Return a specified record.
     *
     * @param Request     $request
     * @param Model|mixed $record
     *
     * @return JsonApiResponse
     */
    public function showAction(Request $request, $record)
    {
        $record = $this->findModelInstance($record);
        $params = $this->getRequestParameters($request);
        $this->validateIncludableRelations($params['include']);

        return new JsonApiResponse(new ResourceSerializer($record, $params['fields'], $params['include']));
    }

    /**
     * Update a specified record.
     *
     * @param Request     $request
     * @param Model|mixed $record
     *
     * @return JsonApiResponse
     */
    public function updateAction(Request $request, $record)
    {
        $record = $this->findModelInstance($record);
        $record->fill((array) $request->input('data.attributes'));
        $record->save();

        if ($relationships = $request->input('data.relationships')) {
            $this->updateResourceRelationships($record, (array) $relationships);
        }

        return new JsonApiResponse(new ResourceSerializer($record));
    }

    /**
     * Destroy a specified record.
     *
     * @param Request     $request
     * @param Model|mixed $record
     *
     * @return JsonApiResponse
     */
    public function destroyAction(Request $request, $record)
    {
        $record = $this->findModelInstance($record);
        $record->delete();

        return new JsonApiResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return a specified record relationship.
     *
     * @param Request     $request
     * @param Model|mixed $record
     * @param string      $relation
     *
     * @return JsonApiResponse
     */
    public function showRelationshipAction(Request $request, $record, $relation)
    {
        $record = $this->findModelInstance($record);

        return new JsonApiResponse(new RelationshipSerializer($record, $relation));
    }

    /**
     * Update a named relationship on a specified record.
     *
     * http://jsonapi.org/format/#crud-updating-relationships
     *
     * @param Request     $request
     * @param Model|mixed $record
     * @param string      $relation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return JsonApiResponse
     */
    public function updateRelationshipAction(Request $request, $record, $relation)
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
     * Return any JSON API resource parameters from a request.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestParameters($request)
    {
        $enableIncluded = config('jsonapi.enable_included_resources');

        if ($request->has('include') && is_bool($enableIncluded) && !$enableIncluded) {
            throw new HttpException('Inclusion of related resources is not supported');
        }

        return [
            'fields' => $this->getRequestQuerySet($request, 'fields', '/^([A-Za-z]+.?)+[A-Za-z]+$/'),
            'include' => $this->getRequestQuerySet($request, 'include', '/^([A-Za-z]+.?)+[A-Za-z]+$/'),
            'sort' => $this->getRequestQuerySet($request, 'sort', '/[A-Za-z_]+/'),
            'filter' => (array) $request->input('filter'),
        ];
    }

    /**
     * Return any comma separated values in a request query field as an array.
     *
     * @param Request     $request
     * @param string      $key
     * @param string|null $validate Regular expression to test for each item
     *
     * @throws \Illuminate\Validation\ValidationException
     *
     * @return array
     */
    protected function getRequestQuerySet($request, $key, $validate = null)
    {
        $values = preg_split('/,/', $request->input($key), null, PREG_SPLIT_NO_EMPTY);

        $validator = Validator::make(['param' => $values], [
            'param.*' => 'required' . ($validate ? '|regex:' . $validate : ''),
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator, $this->error(
                Response::HTTP_BAD_REQUEST,
                sprintf('Invalid values for "%s" parameter', $key))
            );
        }

        return $values;
    }

    /**
     * Validate the requested included relationships against those that are
     * allowed on the requested resource type.
     *
     * @param array|null $relations
     *
     * @throws InvalidRelationPathException
     */
    protected function validateIncludableRelations($relations)
    {
        if (is_null($relations)) {
            return;
        }

        foreach ($relations as $relation) {
            if (!$this->model instanceof IncludesRelatedResources || !in_array($relation, $this->model->getIncludableRelations())) {
                throw new InvalidRelationPathException($relation);
            }
        }
    }

    /**
     * Return the page number and page size to use for paginated results.
     *
     * @param \Illuminate\Http\Request $request
     */
    protected function resolvePaginationParameters($request): array
    {
        return [
            'number' => $request->input('page.number', $request->input('page.offset', 0) / 10 + 1),
            'size' => $request->input('page.size', $request->input('page.limit', $this->model->getPerPage())),
        ];
    }
}
