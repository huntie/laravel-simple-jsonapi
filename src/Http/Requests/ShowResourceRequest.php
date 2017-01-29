<?php

namespace Huntie\JsonApi\Http\Requests;

class ShowResourceRequest extends JsonApiRequest
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
