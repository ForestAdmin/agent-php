<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Count;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Destroy;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Listing;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\AssociateRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\CountRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\DissociateRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\ListingRelated;
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
            HealthCheck::getRoutes(),
            Authentication::getRoutes(),
            Charts::getRoutes(),
            ScopeInvalidation::getRoutes(),
            Listing::getRoutes(),
            Store::getRoutes(),
            Count::getRoutes(),
            Show::getRoutes(),
            Update::getRoutes(),
            Destroy::getRoutes(),
            ListingRelated::getRoutes(),
            AssociateRelated::getRoutes(),
            DissociateRelated::getRoutes(),
            CountRelated::getRoutes(),
        );
    }

    public function makeRoutes()
    {
        return array_merge(
            $this->getRootRoutes()
        );
    }
}
