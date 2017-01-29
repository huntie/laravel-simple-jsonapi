<?php

namespace Huntie\JsonApi\Http\Requests;

class StoreResourceRequest extends JsonApiRequest
{
    /**
     * Base validation rules for the individual request type.
     *
     * @var array
     */
    protected $rules = [
        'data.type' => 'required|string',
        'data.attributes' => 'required|array',
        'data.relationships' => 'array',
        'data.relationships.*.type' => 'required_if:data.relationships|string',
        'data.relationships.*.id' => 'required_if:data.relationships',
    ];
}
