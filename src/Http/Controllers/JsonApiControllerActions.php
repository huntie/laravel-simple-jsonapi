<?php

namespace Huntie\JsonApi\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Add default resource controller methods for JsonApiController actions.
 *
 * @return JsonApiController
 */
trait JsonApiControllerActions
{
    /**
     * Return a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function index(Request $request)
    {
        return $this->indexAction($request);
    }

    /**
     * Store a new record.
     *
     * @param Request $request
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function store(Request $request)
    {
        return $this->storeAction($request);
    }

    /**
     * Return a specified record.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function show(Request $request, $id)
    {
        return $this->showAction($request, $id);
    }

    /**
     * Update a specified record.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function update(Request $request, $id)
    {
        return $this->updateAction($request, $id);
    }

    /**
     * Destroy a specified record.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function destroy(Request $request, $id)
    {
        return $this->destroyAction($request, $id);
    }

    /**
     * Return a specified record relationship.
     *
     * @param Request $request
     * @param int     $id
     * @param string  $relation
     *
     * @return \Huntie\JsonApi\Http\JsonApiResponse
     */
    public function showRelationship(Request $request, $id, $relation)
    {
        return $this->showRelationshipAction($request, $id, $relation);
    }
}
