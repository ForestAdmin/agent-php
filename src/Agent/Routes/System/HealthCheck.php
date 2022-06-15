<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\System;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;

class HealthCheck extends AbstractRoute
{
    public function __construct(protected ForestAdminHttpDriverServices $services, protected array $options)
    {
        parent::__construct($services, $options);
    }

    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $this->addRoute(
            'forest',
            'get',
            '/',
            fn () => $this->handleRequest()
        );

        return $this;
    }

    public function handleRequest()
    {
//        dd($_REQUEST);
        return 'ok';
//        return forestResponse();
    }
}
