<?php

namespace Huntie\JsonApi\Routing;

trait KernelRouterAssociation
{
    /**
     * Get the route dispatcher callback.
     *
     * @return \Closure
     */
    protected function dispatchToRouter()
    {
        // Whilst Laravel provides the package Router instance within all app
        // code, it is hardcoded in the base Kernel class and needs to be set
        // directly here when we are using custom JSON API router extensions.
        parent::__construct($this->app, $this->app['router']);

        return parent::dispatchToRouter();
    }
}
