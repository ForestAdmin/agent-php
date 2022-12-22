<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\System;

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
        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
