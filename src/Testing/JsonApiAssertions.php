<?php

namespace Huntie\JsonApi\Testing;

/**
 * Extend TestCase with additional JSON API related assertions.
 */
trait JsonApiAssertions
{
    /**
     * Assert that all given keys are set in an associative array. Nested
     * members may be specified using dot notation.
     *
     * @param array $keys
     * @param array $array
     *
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function assertArrayHasAll(array $keys, array $array)
    {
        foreach ($keys as $key) {
            if (!array_has($array, $key)) {
                $this->fail('Failed asserting that key "' . $key . '" exists in input array.');
            }
        }
    }

    /**
     * Assert that an array contains a valid JSON API resource identifier.
     *
     * @param array $array
     *
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function assertJsonApiResourceIdentifier(array $array)
    {
        $this->assertArrayHasAll(['data.type', 'data.id'], $array);
    }

    /**
     * Assert that an array contains a valid JSON API resource object.
     *
     * @param array $array
     *
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function assertJsonApiResourceObject(array $array)
    {
        $this->assertArrayHasAll(['data.type', 'data.id', 'data.attributes'], $array);
    }

    /**
     * Assert that an array contains a valid JSON API resource identifier
     * object collection.
     *
     * @param array $array
     * @param int   $count
     *
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function assertJsonApiIdentifierCollection(array $array, $count = null)
    {
        $this->assertArrayHasKey('data', $array, 'No data key for collection');

        foreach ($array['data'] as $identifier) {
            $this->assertArrayHasAll(['type', 'id'], (array) $identifier);
        }

        $this->assertCount($count, $array['data'], 'Incorrect object count returned in collection');
    }

    /**
     * Assert that an array contains a valid JSON API resource object collection.
     *
     * @param array $array
     * @param int   $count
     *
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function assertJsonApiObjectCollection(array $array, $count = null)
    {
        $this->assertArrayHasKey('data', $array, 'No data key for collection');

        foreach ($array['data'] as $object) {
            $this->assertArrayHasAll(['type', 'id', 'attributes'], (array) $object);
        }

        $this->assertCount($count, $array['data'], 'Incorrect object count returned in collection');
    }
}
