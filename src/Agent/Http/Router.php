<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Count;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Destroy;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Listing;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Show;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Store;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Update;
use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\Agent\Routes\Security\ScopeInvalidation;

class Router
{
    private function getRootRoutes(): array
    {
        return array_merge(
            HealthCheck::of()->getRoutes(),
            Authentication::of()->getRoutes(),
            Charts::of()->getRoutes(),
            ScopeInvalidation::of()->getRoutes(),
            Listing::of()->getRoutes(),
            Store::of()->getRoutes(),
            Count::of()->getRoutes(),
            Show::of()->getRoutes(),
            Update::of()->getRoutes(),
            Destroy::of()->getRoutes(),
        );
    }

    public function makeRoutes()
    {
        return array_merge(
            $this->getRootRoutes()
        );
    }
}
