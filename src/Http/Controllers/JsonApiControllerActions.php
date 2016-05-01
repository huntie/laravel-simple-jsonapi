<?php

namespace Huntie\JsonApi\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Add default resource controller methods for JsonApiController actions.
 *
 * @method indexAction(Request $request)
 * @method storeAction(Request $request)
 * @method showAction(Request $request, int $id)
 * @method updateAction(Request $request, int $id)
 * @method destroyAction(Request $request, int $id)
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
}
