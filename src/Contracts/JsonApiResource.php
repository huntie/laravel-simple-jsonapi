<?php

namespace Huntie\JsonApi\Contracts;

interface JsonApiResource
{
    /**
     * The relationships which can be included with this resource.
     *
     * @return array
     */
    public function getIncludableRelations();
}
