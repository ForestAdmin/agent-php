<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\Agent\Routes\Resources\Count;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Destroy;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Listing;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Show;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Store;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Update;
use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;

class Router
{
    private ForestAdminHttpDriverServices $services;

    public function __construct(
    ) {
        $this->services = new ForestAdminHttpDriverServices();
    }

    private function getRootRoutes(): array
    {
        return array_merge(
            HealthCheck::of($this->services)->getRoutes(),
            Authentication::of($this->services)->getRoutes(),
            Listing::of($this->services)->getRoutes(),
            Store::of($this->services)->getRoutes(),
            Count::of($this->services)->getRoutes(),
            Show::of($this->services)->getRoutes(),
            Update::of($this->services)->getRoutes(),
            Destroy::of($this->services)->getRoutes(),
        );
    }

    public function makeRoutes()
    {
        return array_merge(
            $this->getRootRoutes()
        );
    }
}
