<?php

namespace Huntie\JsonApi\Http\Requests;

class UpdateToManyRelationshipRequest extends JsonApiRequest
{
    /**
     * Base validation rules for the individual request type.
     *
     * @var array
     */
    protected $rules = [
        'data.*.type' => 'required',
        'data.*.id' => 'required',
        'data.*.attributes' => 'array',
    ];
}
