<?php

namespace Huntie\JsonApi\Tests\Fixtures\Routes;

use Huntie\JsonApi\Tests\Fixtures\Controllers\UserController;

/**
 * Define example application routes for testing.
 */
trait ExampleRoutes
{
    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function getEnvironmentSetup($app)
    {
        $app['router']->resource('users', UserController::class);
    }
}
