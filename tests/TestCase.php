<?php

namespace Tests;

use Huntie\JsonApi\Testing\JsonApiAssertions;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use JsonApiAssertions;

    /**
     * Set up the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/Support/Factories');

        $this->loadMigrationsFrom([
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__ . '/database/migrations'),
        ]);
    }

    /**
     * Define Testbench app environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function getEnvironmentSetup($app)
    {
        $app['config']->set([
            'database.default' => 'testbench',
            'database.connections.testbench' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
            'jsonapi.model_namespace' => 'Tests\Fixtures\Models',
        ]);

        $this->registerAppRoutes($app);
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Orchestra\Database\ConsoleServiceProvider::class,
            \Huntie\JsonApi\Providers\JsonApiServiceProvider::class,
        ];
    }

    /**
     * Register test application routes.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function registerAppRoutes($app)
    {
        $app['router']->resources([
            'users' => \Tests\Fixtures\Controllers\UserController::class,
            'posts' => \Tests\Fixtures\Controllers\PostController::class,
        ]);
    }
}
