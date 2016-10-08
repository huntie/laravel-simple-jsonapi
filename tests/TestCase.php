<?php

namespace Huntie\JsonApi\Tests;

use Huntie\JsonApi\Tests\Support\JsonApiAssertions;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use JsonApiAssertions;

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
