<?php

namespace Huntie\JsonApi\Tests;

use Huntie\JsonApi\Tests\Support\JsonApiAssertions;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use JsonApiAssertions;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/Support/Factories');
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
            \Huntie\JsonApi\JsonApiServiceProvider::class,
        ];
    }
}
