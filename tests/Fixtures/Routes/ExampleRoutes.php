<?php

namespace Tests\Fixtures\Routes;

use Tests\Fixtures\Controllers\UserController;

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
