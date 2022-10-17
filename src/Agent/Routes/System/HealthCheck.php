<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\System;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;

class HealthCheck extends AbstractRoute
{
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
        $this->sendSchema();

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    /**
     * @codeCoverageIgnore
     * @return void
     */
    protected function sendSchema(): void
    {
        ForestAdminHttpDriver::sendSchema(AgentFactory::get('datasource'));
    }
}
