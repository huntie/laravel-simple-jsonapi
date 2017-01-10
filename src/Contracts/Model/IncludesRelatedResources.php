<?php

namespace Huntie\JsonApi\Contracts\Model;

interface IncludesRelatedResources
{
    /**
     * The relationships which can be included with this resource.
     *
     * @return array
     */
    public function getIncludableRelations();
}
