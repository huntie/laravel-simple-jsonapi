<?php

namespace Huntie\JsonApi\Routing;

class Router extends \Illuminate\Routing\Router
{
    /**
     * Register an array of JSON API resource controllers.
     *
     * @param array $resources
     */
    public function jsonApiResources(array $resources)
    {
        foreach ($resources as $name => $controller) {
            $this->resource($name, $controller);
        }
    }

    /**
     * Route a JSON API resource to a controller.
     *
     * @param string $name
     * @param string $controller
     * @param array  $options
     */
    public function jsonApiResource($name, $controller, array $options = [])
    {
        if ($this->container && $this->container->bound(ResourceRegistrar::class)) {
            $registrar = $this->container->make(ResourceRegistrar::class);
        } else {
            $registrar = new ResourceRegistrar($this);
        }

        $registrar->register($name, $controller, $options);
    }
}
