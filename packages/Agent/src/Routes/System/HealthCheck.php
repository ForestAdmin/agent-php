<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\System;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;

use function ForestAdmin\config;

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
        // @codeCoverageIgnoreStart
        if (config('isProduction')) {
            AgentFactory::sendSchema(true);
        }
        // @codeCoverageIgnoreEnd

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
