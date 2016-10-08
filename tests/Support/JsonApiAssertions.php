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
     * @return $this
     */
    public function seeJsonApiObject()
    {
        return $this->seeJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes',
            ]
        ]);
    }

    /**
     * Assert that a document containing a valid JSON API collection is returned.
     *
     * @return $this
     */
    public function seeJsonApiCollection()
    {
        return $this->seeJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes',
                ]
            ]
        ]);
    }
}
