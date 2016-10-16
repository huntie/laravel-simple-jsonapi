<?php

namespace Huntie\JsonApi\Tests\Support;

/**
 * Extend TestCase with additional JSON API assertions.
 *
 * @method seeJsonStructure
 */
trait JsonApiAssertions
{
    /**
     * Assert that a document containing a valid JSON API object is returned.
     *
     * @param array|null $responseData
     *
     * @return $this
     */
    public function seeJsonApiObject($responseData = null)
    {
        return $this->seeJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes',
            ]
        ], $responseData);
    }

    /**
     * Assert that a document containing a valid JSON API collection is returned.
     *
     * @param array|null $responseData
     *
     * @return $this
     */
    public function seeJsonApiCollection($responseData = null)
    {
        return $this->seeJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes',
                ]
            ]
        ], $responseData);
    }
}
