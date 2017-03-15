# Laravel Simple JSON API

[![Build status](https://img.shields.io/scrutinizer/build/g/huntie/laravel-simple-jsonapi.svg?maxAge=60&style=flat-square)](https://scrutinizer-ci.com/g/huntie/laravel-simple-jsonapi/build-status/develop)
[![Code quality](https://img.shields.io/scrutinizer/g/huntie/laravel-simple-jsonapi.svg?maxAge=60&style=flat-square)](https://scrutinizer-ci.com/g/huntie/laravel-simple-jsonapi)
[![Packagist](https://img.shields.io/packagist/vpre/huntie/laravel-simple-jsonapi.svg?maxAge=60&style=flat-square)](https://packagist.org/packages/huntie/laravel-simple-jsonapi)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?maxAge=2592000&style=flat-square)](https://github.com/huntie/laravel-simple-jsonapi/blob/master/LICENSE.txt)

An implementation of the [JSON API](http://jsonapi.org/) specification for Laravel with minimal configuration. Designed to work closely with Eloquent Model features.

> This library is in initial development and is not yet suitable for production use. Expect tests and documentation in time.

## Installation

1.  Install the latest pre-release using [Composer](https://getcomposer.org/).

        $ composer require huntie/laravel-simple-jsonapi

2.  Add a reference to `JsonApiServiceProvider` under the `providers` array in `config/app.php`.

    ```php
    Huntie\JsonApi\JsonApiServiceProvider::class
    ```

3.  Run the following Artisan command to publish a local configuration file at `config/jsonapi.php`.

        $ php artisan vendor:publish

## Usage

The main class in this package is `JsonApiController`, which provides a full set of create, read, update and delete actions for a given Eloquent Model. A bunch of query parameters are supported which will affect the JSON API Objects returned, and you can also fetch and update model relationships.

### 1. Define routes

Add a [RESTful resource route](https://laravel.com/docs/master/controllers#restful-resource-controllers) for the target model in `routes.php`.

```php
Route::resource('users', 'UserController');
```

Using the `'only'` key here allows you to enable a only subset of resource endpoints before they hit the controller:

```php
Route::resource('users', 'UserController', [
    'only' => ['index', 'show', 'update'],
]);
```

### 2. Add controller

Our new controller for this resource needs to extend `JsonApiController` and use the `JsonApiControllerActions` trait. The model for the primary resource is inferred by default from the controller name, but this can also be set on the `$model` property.

```php
<?php

namespace App\Http\Controllers;

use Huntie\JsonApi\Http\Controllers\JsonApiController;
use Huntie\JsonApi\Http\Controllers\JsonApiControllerActions;

class UserController extends JsonApiController
{
    use JsonApiControllerActions;

    /**
     * The related Eloquent Model.
     *
     * @var Model|string
     */
    protected $model = 'App\User';
}
```

The trait `JsonApiControllerActions` is important. It defines each endpoint `index`, `store`, `show`, `update`, `destroy` at the class level and calls the relevant parent controller method, e.g. `indexAction`. This allows us to override particular controller actions, meaning we can specify additional parameters as well as a different type-hinted request class for [Form Request Validation](https://laravel.com/docs/master/validation#form-request-validation).

The controller now will respond to each endpoint for this resource where a route has been defined.

#### Implicit model transformation

Whenever a model is tranformed into a JSON API Object or Collection, the built-in properties and methods defined on your Eloquent Model, such as `$casts`, `$hidden`, and `$appends` will apply automatically, removing the need for separate model tranformation logic. See [the Laravel docs](https://laravel.com/docs/master/eloquent) for more information on what is available.

This package uses these Eloquent features heavily – the examples demonstrate further how these are applied.

## Examples

Coming soon.

## Resource options

### Inclusion of related resources

To allow [including related resources](http://jsonapi.org/format/#fetching-includes) on a given resource, the model for the primary resource should implement the `IncludesRelatedResources` interface, providing a whitelist of relationship paths that can be included.

```php
namespace App\Http\Requests\User;

use Huntie\JsonApi\Contracts\Model\IncludesRelatedResources;

class Post extends Model implements IncludesRelatedResources
{
    /**
     * The relationships which can be included with this resource.
     *
     * @return array
     */
    public function getIncludableRelations()
    {
        return [
            'author',
            'author.profile',
        ];
    }
}
```

## Error handling

There are a number of contexts where you may return error responses as formatted JSON API Error Objects.

### Form validation

Implementing a [Form Request validation class](https://laravel.com/docs/master/validation#form-request-validation) that extends `JsonApiRequest` will format any validation errors appropriately when a validation error occurs.

```php
<?php

namespace App\Http\Requests\User;

use Auth;
use Huntie\JsonApi\Http\Requests\JsonApiRequest;

class UpdateUserRequest extends JsonApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->route('user')->id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'data.type' => 'required|in:users',
            'data.attributes.name' => 'required|max:255',
            'data.attributes.email' => 'required|email|unique:users,email',
            'data.attributes.password' => 'required|min:6',
        ];
    }
}
```

The validation rules are applied by type-hinting the request class in your controller method and then calling the associated parent action.

```php
    /**
     * Update a specified user.
     *
     * @param UpdateUserRequest $request
     * @param User              $user
     *
     * @return JsonApiResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        return $this->updateAction($request, $user);
    }
```

A subsequent POST request to `/users/{user}` with multiple validation errors will return a validation error response with pointers to each invalid attribute.

    HTTP/1.1 422 Unprocessable Entity
    Content-Type: application/vnd.api+json

```json
{
    "errors": [
        {
            "source": { "pointer": "data/attributes/name" },
            "title": "Invalid attribute",
            "detail": "The name field is required."
        },
        {
            "source": { "pointer": "data/attributes/password" },
            "title": "Invalid attribute",
            "detail": "The password must be at least 6 characters."
        }
    ]
}
```

### JsonApiErrors trait

The `JsonApiErrors` trait is a convenient helper for returning JSON API error responses following any request.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Huntie\JsonApi\Support\JsonApiErrors;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Authenticate
{
    use JsonApiErrors;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->headers->get('Authorization')) {
            return $this->error(Reponse::HTTP_BAD_REQUEST, 'No token provided');
        }

        // Further logic

        return $next($request);
    }
}
```

Invalid requests to routes with this middleware will return a formatted error object. Optionally, a third parameter can be provided to `error()`, which will add a `"detail"` value.

    HTTP/1.1 400 Bad Request
    Content-Type: application/vnd.api+json

```json
{
    "errors": [
        {
            "status": "400",
            "title": "No token provided"
        }
    ]
}
```

## Model Serializers

A number of Serializer classes are available to help you convert Eloquent models to valid JSON API documents in any context. Each Serializer provides a `serializeToObject()` and `serializeToJson()` method, and implements the [`JsonSerializable`](http://php.net/JsonSerializable) interface.

### ResourceSerializer

```php
$serializer = new ResourceSerializer(User::first());
return $serializer->serializeToJson();
```

In the constructor, you can optionally provide a second parameter specifying the array of fields to return, and a third parameter specifying which relationships to include.

```php
$serializer = new ResourceSerializer(Article::first(), ['articles.title', 'users.name'], ['author', 'comments']);
```

In `ResourceSerializer`, you can also choose to return JSON API resource identifier and object representations directly.

```php
$serializer->toResourceObject();
$serializer->toResourceIdentifier();
```

### CollectionSerializer

`CollectionSerializer` can be instantiated both with a collection of Models, or a `LengthAwarePaginator`. When a paginator instance is provided, pagination links and the total count are returned in the resulting document.

```php
$serializer = new CollectionSerializer(User::paginate(10));
return $serializer->serializeToJson();
```

`CollectionSerializer` can also be scoped to particular fields and return any included records from a given set of relationships.

```php
$serializer = new CollectionSerializer(User::all(), ['users.email'], ['roles']);
```

### RelationshipSerializer

`RelationshipSerializer` is instantiated with a primary model instance and the name of the relationship to transform. The returned document will contain a [resource linkage](http://jsonapi.org/format/#fetching-relationships) representing the relationship.

```php
$serializer = new RelationshipSerializer(User::first(), 'roles');
return $serializer->serializeToJson();
```

## Contributing

If you discover a problem or have a feature request, please [create an issue](https://github.com/huntie/laravel-simple-jsonapi/issues) or feel free to fork this repository and make improvements.
