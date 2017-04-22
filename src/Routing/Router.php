<?php

namespace Huntie\JsonApi\Routing;

class Router extends \Illuminate\Routing\Router
{
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
