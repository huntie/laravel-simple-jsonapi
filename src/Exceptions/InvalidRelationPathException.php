<?php

namespace Huntie\JsonApi\Exceptions;

use Illuminate\Http\Response;

class InvalidRelationPathException extends HttpException
{
    /**
     * Create a new InvalidRelationPathException instance.
     *
     * @param string $path The relation path attempted
     */
    public function __construct($path, $response = null)
    {
        parent::__construct(
            sprintf('The relationship path "%s" could not be resolved', $path),
            Response::HTTP_BAD_REQUEST
        );
    }
}
