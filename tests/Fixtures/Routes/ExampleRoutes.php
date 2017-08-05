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
        $app['config']->set('jsonapi.model_namespace', 'Tests\Fixtures\Models');
        $this->registerAppRoutes($app);
    }

    /**
     * Register test application routes.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function registerAppRoutes($app)
    {
        $app['router']->resource('users', UserController::class);
    }
}
