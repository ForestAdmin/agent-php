<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class Router
{
    public function __construct(
        protected Datasource $dataSource,
        protected ForestAdminHttpDriver $httpDriver,
        protected array $options,
        protected ForestAdminHttpDriverServices $services
    ) {
    }

    private function getRootRoutes(): array
    {
        return array_merge(
            HealthCheck::of($this->services, $this->options, $this->httpDriver)->getRoutes(),
        );
    }

    public function makeRoutes()
    {
        return array_merge(
            $this->getRootRoutes()
        );
    }
}
