<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\Agent\Routes\Access\Listing;
use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class Router
{
    public function __construct(
        protected ForestAdminHttpDriver $httpDriver,
        protected ForestAdminHttpDriverServices $services
    ) {
    }

    private function getRootRoutes(): array
    {
        return array_merge(
            HealthCheck::of($this->services, $this->httpDriver)->getRoutes(),
            Authentication::of($this->services)->getRoutes(),
            Listing::of($this->services)->getRoutes()
        );
    }

    public function makeRoutes()
    {
        return array_merge(
            $this->getRootRoutes()
        );
    }
}
