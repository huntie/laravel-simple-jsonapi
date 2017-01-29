<?php

namespace Huntie\JsonApi\Http\Requests;

class UpdateToOneRelationshipRequest extends JsonApiRequest
{
    /**
     * Base validation rules for the individual request type.
     *
     * @var array
     */
    protected $rules = [
        'data.type' => 'required|string',
        'data.id' => 'required',
    ];
}
