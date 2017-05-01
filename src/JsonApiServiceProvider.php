<?php

namespace Huntie\JsonApi;

use Illuminate\Support\ServiceProvider;

class JsonApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        //
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
