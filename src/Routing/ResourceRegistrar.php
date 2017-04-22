<?php

namespace Huntie\JsonApi\Routing;

class ResourceRegistrar extends \Illuminate\Routing\ResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = ['index', 'store', 'show', 'update', 'destroy'];

    /**
     * The default actions for each named resource relationship.
     *
     * @var array
     */
    protected $relationshipDefaults = ['show'];

    /**
     * Create a new resource registrar instance.
     *
     * @param \Huntie\JsonApi\Routing\Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Route a resource to a controller.
     *
     * @param string $name
     * @param string $controller
     * @param array  $options
     */
    public function register($name, $controller, array $options = [])
    {
        parent::register($name, $controller, $options);

        $this->registerRelationships($name, $controller, $options);
    }

    /**
     * Route any resource relationships to a controller.
     *
     * @param string $name
     * @param string $controller
     * @param array  $options
     */
    protected function registerRelationships($name, $controller, array $options)
    {
        $base = $this->getResourceWildcard(last(explode('.', $name)));

        // Map non-associative members as keys, with default relationship actions
        $relationships = collect(array_get($options, 'relationships', []))
            ->mapWithKeys(function ($methods, $relationship) {
                return is_numeric($relationship)
                    ? [$methods => $this->relationshipDefaults]
                    : [$relationship => (array) $methods];
            });

        foreach (['show', 'update'] as $action) {
            $matched = $relationships->filter(function ($methods) use ($action) {
                return in_array($action, $methods);
            })->keys()->toArray();

            if (!empty($matched)) {
                $this->{'addRelationship' . ucfirst($action)}($name, $base, $matched, $controller, $options);
            }
        }
    }

    /**
     * Add a relationship show method to match named relationships on the resource.
     *
     * @param string $name
     * @param string $base
     * @param array  $relationships
     * @param string $controller
     * @param array  $options
     *
     * @return \Illuminate\Routing\Route
     */
    protected function addRelationshipShow($name, $base, array $relationships, $controller, array $options)
    {
        $uri = $this->getRelationshipUri($name, $base);
        $action = $this->getResourceAction($name, $controller, 'showRelationship', $options);

        return $this->router->get($uri, $action)
            ->where('relationship', '(' . implode(')|(', $relationships) . ')');
    }

    /**
     * Add a relationship update method to match named relationships on the resource.
     *
     * @param string $name
     * @param string $base
     * @param array  $relationships
     * @param string $controller
     * @param array  $options
     *
     * @return \Illuminate\Routing\Route
     */
    protected function addRelationshipUpdate($name, $base, array $relationships, $controller, array $options)
    {
        $uri = $this->getRelationshipUri($name, $base);
        $action = $this->getResourceAction($name, $controller, 'updateRelationship', $options);

        return $this->router->match(['PUT', 'PATCH'], $uri, $action)
            ->where('relationship', '(' . implode(')|(', $relationships) . ')');
    }

    /**
     * Get the URI for resource relationships.
     *
     * @param string $name
     * @param string $base
     *
     * @return string
     */
    public function getRelationshipUri($name, $base)
    {
        return sprintf('%s/{%s}/relationships/{relationship}', $this->getResourceUri($name), $base);
    }

    /**
     * Format a resource parameter for usage.
     *
     * @param string $value
     *
     * @return string
     */
    public function getResourceWildcard($value)
    {
        if (isset($this->parameters[$value])) {
            return $this->parameters[$value];
        } else if (isset(static::$parameterMap[$value])) {
            return static::$parameterMap[$value];
        }

        if ($this->parameters === 'singular' || static::$singularParameters) {
            $value = str_singular($value);
        }

        return camel_case($value);
    }
}
