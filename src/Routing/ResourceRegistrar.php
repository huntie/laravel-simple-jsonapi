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
     * Create a new resource registrar instance.
     *
     * @param \Huntie\JsonApi\Routing\Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
}
