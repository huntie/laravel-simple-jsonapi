<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Model namespace
    |--------------------------------------------------------------------------
    |
    | The full namespace containing the application's models. Used when
    | inferring the primary resource model in JsonApiController, which can
    | also be set manually through the $model property. Defaults to the
    | application namespace.
    |
    */

    'model_namespace' => '',

    /*
    |--------------------------------------------------------------------------
    | Singular resource type names
    |--------------------------------------------------------------------------
    |
    | Set whether to use singular naming when resolving a JSON API resource
    | type identifier from a model name. By default, these are plural to align
    | with plural resource URIs.
    |
    | http://jsonapi.org/format/#document-resource-object-identification
    |
    */

    'singular_type_names' => false,

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
    | implementing \Huntie\JsonApi\Contracts\Model\IncludesRelatedResources.
    |
    | http://jsonapi.org/format/#fetching-includes
    |
    */

    'enable_included_resources' => true,

    /*
    |--------------------------------------------------------------------------
    | Pagination method
    |--------------------------------------------------------------------------
    |
    | Set the pagination strategy used when returning resource collections.
    | Supported values are 'page-based' and 'offset-based'.
    |
    | http://jsonapi.org/format/#fetching-pagination
    |
    */

    'pagination_method' => 'page-based',

];
