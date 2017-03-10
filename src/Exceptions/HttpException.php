<?php

namespace Huntie\JsonApi\Exceptions;

use Huntie\JsonApi\Support\JsonApiErrors;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;

class_alias(
    version_compare(Application::VERSION, '5.4.0', '>=') ?
        'Illuminate\Http\Exceptions\HttpResponseException' :
        'Illuminate\Http\Exception\HttpResponseException',
    __NAMESPACE__ . '\HttpResponseException'
);

class HttpException extends HttpResponseException
{
    use JsonApiErrors;

    /**
     * Create a new HttpException instance.
     *
     * @param string            $message  The message for this exception
     * @param Response|int|null $response The response object or HTTP status code send to the client
     */
    public function __construct($message, $response = null)
    {
        if (is_null($response)) {
            $response = $this->error(Response::HTTP_BAD_REQUEST, $message);
        } else if (is_int($response)) {
            $response = $this->error($response, $message);
        }

        parent::__construct($response);
    }
}
