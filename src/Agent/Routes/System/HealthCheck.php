<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\System;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;

use function ForestAdmin\cache;

class HealthCheck extends AbstractRoute
{
    public function __construct(
        ForestAdminHttpDriverServices $services,
    ) {
        parent::__construct($services);
    }

    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $this->addRoute(
            'forest',
            'get',
            '',
            fn () => $this->handleRequest()
        );

        return $this;
    }

    public function handleRequest()
    {
        ForestAdminHttpDriver::sendSchema(AgentFactory::get('datasource'));

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
