<?php

namespace Huntie\JsonApi\Tests\Fixtures\Controllers;

use Huntie\JsonApi\Http\Controllers\JsonApiController;
use Huntie\JsonApi\Http\Controllers\JsonApiControllerActions;
use Huntie\JsonApi\Tests\Fixtures\Models\User;

class UserController extends JsonApiController
{
    use JsonApiControllerActions;

    /**
     * Return the related Eloquent Model.
     *
     * @return Model
     */
    public function getModel()
    {
        return new User();
    }
}
