<?php

namespace Huntie\JsonApi\Http\Controllers;

use Schema;
use Huntie\JsonApi\Http\JsonApiResponse;
use Huntie\JsonApi\Support\JsonApiErrors;
use Huntie\JsonApi\Support\JsonApiTransforms;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

abstract class JsonApiController extends Controller
{
    use JsonApiErrors, JsonApiTransforms, AuthorizesRequests, ValidatesRequests;

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
        return str_slug($this->getModel()->getTable());
    }

    /**
     * The model relationships that can be updated.
     *
     * @return array
     */
    protected function getModelRelationships()
    {
        return [];
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
        $records = $query ?: $this->getModel()->newQuery();
        $params = $this->getRequestParameters($request);

        $records = $this->sortQuery($records, $params['sort']);
        $records = $this->filterQuery($records, $params['filter']);

        try {
            $pageSize = min($this->getModel()->getPerPage(), $request->input('page.size'));
            $pageNumber = $request->input('page.number') ?: 1;

            $records = $records->paginate($pageSize, null, 'page', $pageNumber);
        } catch (QueryException $e) {
            return $this->error(Response::HTTP_BAD_REQUEST, 'Invalid query parameters');
        }

        return new JsonApiResponse($this->transformCollection($records, $params['fields'], $params['include']));
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

        if ($relationships = $request->input('data.relationships')) {
            $this->updateRecordRelationships($record, (array) $relationships);
        }

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
        $record = $this->findModelInstance($record);
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
        $record = $this->findModelInstance($record);
        $record->fill((array) $request->input('data.attributes'));
        $record->save();

        if ($relationships = $request->input('data.relationships')) {
            $this->updateRecordRelationships($record, (array) $relationships);
        }

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
        $record = $this->findModelInstance($record);
        $record->delete();

        return new JsonApiResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return a specified record relationship.
     *
     * @param Request   $request
     * @param Model|int $record
     * @param string    $relation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return JsonApiResponse
     */
    public function relationshipAction(Request $request, $record, $relation)
    {
        abort_if(!array_key_exists($relation, $this->getModelRelationships()), Response::HTTP_NOT_FOUND);

        $record = $this->findModelInstance($record);

        return new JsonApiResponse($this->transformRelationship($record->{$relation}));
    }

    /**
     * Update a named many-to-one relationship association on a specified record.
     * http://jsonapi.org/format/#crud-updating-to-one-relationships
     *
     * @param Request     $request
     * @param Model|int   $record
     * @param string      $relation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return JsonApiResponse
     */
    public function updateToOneRelationshipAction(Request $request, $record, $relation)
    {
        abort_if(!array_key_exists($relation, $this->getModelRelationships()), Response::HTTP_NOT_FOUND);

        $record = $this->findModelInstance($record);
        $relation = $this->getModelRelationships()[$relation];
        $data = (array) $request->input('data');

        $record->{$relation->getForeignKey()} = $data['id'];
        $record->save();

        return new JsonApiResponse();
    }

    /**
     * Update named many-to-many relationship entries on a specified record.
     * http://jsonapi.org/format/#crud-updating-to-many-relationships
     *
     * @param Request   $request
     * @param Model|int $record
     * @param string    $relation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return JsonApiResponse
     */
    public function updateToManyRelationshipAction(Request $request, $record, $relation)
    {
        abort_if(!array_key_exists($relation, $this->getModelRelationships()), Response::HTTP_NOT_FOUND);

        $record = $this->findModelInstance($record);
        $relationships = (array) $request->input('data');
        $items = [];

        foreach ($relationships as $item) {
            if (isset($item['attributes'])) {
                $items[$item['id']] = $item['attributes'];
            } else {
                $items[] = $item['id'];
            }
        }

        switch ($request->method()) {
            case 'PATCH':
                $record->{$relation}()->sync($items);
                break;
            case 'POST':
                $record->{$relation}()->sync($items, false);
                break;
            case 'DELETE':
                $record->{$relation}()->detach(array_keys($items));
        }

        return new JsonApiResponse();
    }

    /**
     * Return existing instance of the resource or find by primary key.
     *
     * @param Model|int $record
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

        return $this->getModel()->findOrFail($record);
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
            'sort' => $this->getRequestQuerySet($request, 'sort'),
            'filter' => (array) $request->input('filter'),
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
     * Sort a resource query by one or more attributes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array                                 $attributes
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function sortQuery($query, $attributes)
    {
        foreach ($attributes as $expression) {
            $direction = substr($expression, 0, 1) === '-' ? 'desc' : 'asc';
            $column = preg_replace('/^\-/', '', $expression);
            $query = $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Filter a resource query by one or more attributes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array                                 $attributes
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function filterQuery($query, $attributes)
    {
        $searchableColumns = Schema::getColumnListing($this->getModel()->getTable());

        foreach ($attributes as $column => $value) {
            if (!in_array($column, $searchableColumns)) {
                continue;
            }

            if (is_numeric($value)) {
                // Exact numeric match
                $query = $query->where($column, $value);
            } else if (in_array(strtolower($value), ['true', 'false'])) {
                // Boolean match
                $query = $query->where($column, filter_var($value, FILTER_VALIDATE_BOOLEAN));
            } else {
                // Partial string match
                $query = $query->where($column, 'like', '%' . $value . '%');
            }
        }

        return $query;
    }

    /**
     * Update one or more relationships on a model instance.
     *
     * @param Model $record
     * @param array $relationships
     */
    protected function updateRecordRelationships($record, array $relationships)
    {
        $relationships = array_intersect_key($relationships, $this->getModelRelationships());

        foreach ($relationships as $name => $relationship) {
            $relation = $this->getModelRelationships()[$name];
            $data = $relationship['data'];

            if ($relation instanceof BelongsTo) {
                $record->{$relation->getForeignKey()} = $data['id'];
                $record->save();
            } else if ($relation instanceof BelongsToMany) {
                $record->{$name}()->sync(array_pluck($data, 'id'));
            }
        }
    }
}
