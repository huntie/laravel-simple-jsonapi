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
     * Assert that a document containing a valid JSON API resource identifier
     * object is returned.
     *
     * @param array|null $responseData
     *
     * @return $this
     */
    public function seeJsonApiResourceIdentifier($responseData = null)
    {
        return $this->seeJsonStructure([
            'data' => [
                'type',
                'id',
            ]
        ], $responseData);
    }

    /**
     * Assert that a document containing a valid JSON API resource object is
     * returned.
     *
     * @param array|null $responseData
     *
     * @return $this
     */
    public function seeJsonApiResourceObject($responseData = null)
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
     * Assert that a document containing a valid JSON API resource identifier
     * object collection is returned.
     *
     * @param array|null $responseData
     * @param int|null   $count
     *
     * @return $this
     */
    public function seeJsonApiIdentifierCollection($responseData = null, $count = null)
    {
        return $this->seeJsonApiCollection([
            'type',
            'id',
        ], $responseData, $count);
    }

    /**
     * Assert that a document containing a valid JSON API resource object
     * collection is returned.
     *
     * @param array|null $responseData
     * @param int|null   $count
     *
     * @return $this
     */
    public function seeJsonApiObjectCollection($responseData = null, $count = null)
    {
        return $this->seeJsonApiCollection([
            'type',
            'id',
            'attributes',
        ], $responseData, $count);
    }

    /**
     * Assert that a JSON API document contains a collection of objects defined
     * by a given pattern.
     *
     * @param array      $pattern
     * @param array|null $responseData
     * @param int|null   $count
     *
     * @return $this
     */
    private function seeJsonApiCollection($pattern, $responseData = null, $count = null)
    {
        if (!$responseData) {
            $responseData = json_decode($this->response->getContent(), true);
        }

        $this->seeJsonStructure([
            'data' => [
                '*' => $pattern,
            ]
        ], $responseData);

        if ($count) {
            $this->assertCount($count, $responseData['data'], 'Incorrect object count returned in collection');
        }

        return $this;
    }
}
