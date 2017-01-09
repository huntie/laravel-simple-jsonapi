<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Include JSON API version
    |--------------------------------------------------------------------------
    |
    | Set whether the implemented JSON API version should be included in a
    | `jsonapi` object in the top-level document for each response.
    |
    | http://jsonapi.org/format/#document-jsonapi-object
    |
    */

    'include_version' => false,

    /*
    |--------------------------------------------------------------------------
    | Enable inclusion of related resources
    |--------------------------------------------------------------------------
    |
    | Set whether index and show endpoints for resources should support
    | including related records when the 'included' parameter is sent in a
    | request. A whitelist of enabled relations can be set per-model by
    | returning an array from getIncludableRelations when implementing
    | JsonApiResource.
    |
    | http://jsonapi.org/format/#fetching-includes
    |
    */

    'enable_included_resources' => true,

];
