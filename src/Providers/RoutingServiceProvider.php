<?php

namespace Huntie\JsonApi\Providers;

use Huntie\JsonApi\Routing\ResourceRegistrar;
use Huntie\JsonApi\Routing\Router;
use Illuminate\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
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
}
