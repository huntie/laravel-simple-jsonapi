<?php

namespace Huntie\JsonApi\Support;

use Huntie\JsonApi\Http\JsonApiResponse;

/**
 * Format JSON API error responses.
 * http://jsonapi.org/format/#error-objects
 */
trait JsonApiErrors
{
    /**
     * Return an error response containing a JSON API errors object.
     *
     * @param int         $status
     * @param string      $title
     * @param string|null $detail
     *
     * @return JsonApiResponse
     */
    public function error($status, $title, $detail = null)
    {
        $error = compact('status', 'title');

        if (!is_null($detail)) {
            $error['detail'] = $detail;
        }

        return new JsonApiResponse(['errors' => [$error]], $status);
    }
}
