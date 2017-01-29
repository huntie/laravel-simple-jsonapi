<?php

namespace Huntie\JsonApi\Http\Controllers;

use Huntie\JsonApi\Http\Requests\ListResourceRequest;
use Huntie\JsonApi\Http\Requests\StoreResourceRequest;
use Huntie\JsonApi\Http\Requests\ShowResourceRequest;
use Huntie\JsonApi\Http\Requests\UpdateResourceRequest;
use Huntie\JsonApi\Http\Requests\DestroyResourceRequest;
use Huntie\JsonApi\Http\Requests\ShowRelationshipRequest;

/**
 * Add default resource controller methods for JsonApiController actions.
 *
 * @method indexAction($request)
 * @method storeAction($request)
 * @method showAction($request, $id)
 * @method updateAction($request, $id)
 * @method destroyAction($request, $id)
 * @method showRelationshipAction($request, $id, $relation)
 */
trait JsonApiControllerActions
{
    /**
     * Return a listing of the resource.
     *
     * @param ListResourceRequest $request
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function index(ListResourceRequest $request)
    {
        return $this->indexAction($request);
    }

    /**
     * Store a new record.
     *
     * @param StoreResourceRequest $request
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function store(StoreResourceRequest $request)
    {
        return $this->storeAction($request);
    }

    /**
     * Return a specified record.
     *
     * @param ShowResourceRequest $request
     * @param int                 $id
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function show(ShowResourceRequest $request, $id)
    {
        return $this->showAction($request, $id);
    }

    /**
     * Update a specified record.
     *
     * @param UpdateResourceRequest $request
     * @param int                   $id
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function update(UpdateResourceRequest $request, $id)
    {
        return $this->updateAction($request, $id);
    }

    /**
     * Destroy a specified record.
     *
     * @param DestroyResourceRequest $request
     * @param int                    $id
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function destroy(DestroyResourceRequest $request, $id)
    {
        return $this->destroyAction($request, $id);
    }

    /**
     * Return a specified record relationship.
     *
     * @param ShowRelationshipRequest $request
     * @param int                     $id
     * @param string                  $relation
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function showRelationship(ShowRelationshipRequest $request, $id, $relation)
    {
        return $this->showRelationshipAction($request, $id, $relation);
    }
}
