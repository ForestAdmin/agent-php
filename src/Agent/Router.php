<?php

namespace ForestAdmin\AgentPHP\Agent;

use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class Router
{
    public function __construct(
        protected Datasource $dataSource,
        protected array $options,
        protected ForestAdminHttpDriverServices $services
    ) {
    }

    private function getRootRoutes(): array
    {

//        return ROOT_ROUTES_CTOR.map(Route => new Route(services, options));
        return [
            new Authentication($this->services, $this->options),
            new HealthCheck($this->services, $this->options),
        ];
    }


    public function makeRoutes()
    {
        $routes = [
            ...$this->getRootRoutes()
        ];

        return $routes;
    }
}
