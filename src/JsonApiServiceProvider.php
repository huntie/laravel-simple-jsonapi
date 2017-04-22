<?php

namespace Huntie\JsonApi;

use Huntie\JsonApi\Routing\ResourceRegistrar;
use Huntie\JsonApi\Routing\Router;
use Illuminate\Support\ServiceProvider;

class JsonApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton('router', function ($app) {
            return new Router($app['events'], $app);
        });

        $this->app->bind('Huntie\JsonApi\Routing\ResourceRegistrar', function ($app) {
            return new ResourceRegistrar($app['router']);
        });
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->publishes([
            realpath(__DIR__ . '/../config/jsonapi.php') => config_path('jsonapi.php'),
        ]);
    }
}
