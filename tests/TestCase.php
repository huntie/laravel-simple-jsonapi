<?php

namespace Huntie\JsonApi\Tests;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
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
            \Huntie\JsonApi\JsonApiServiceProvider::class,
        ];
    }
}
