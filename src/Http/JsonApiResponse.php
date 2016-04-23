<?php

namespace Huntie\JsonApi\Http;

use Illuminate\Http\JsonResponse;

class JsonApiResponse extends JsonResponse
{
    /**
     * Create a new JsonApiResponse instance.
     *
     * {@inheritdoc}
     */
    public function __construct($data = null, $status = 200, $headers = [], $options = 0)
    {
        $headers['Content-Type'] = 'application/vnd.api+json';

        parent::__construct($data, $status, $headers, $options);
    }
}
