<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Routes\Actions\Actions;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\ApiChartCollection;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\ApiChartDatasource;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Count;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Destroy;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Listing;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\AssociateRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\CountRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\DissociateRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\ListingRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\UpdateRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Show;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Store;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Update;
use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Routes\Security\ScopeInvalidation;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;

class Router
{
    public static function getRoutes(): array
    {
        return array_merge(
            self::getActionsRoutes(),
            self::getApiChartsRoutes(),
            HealthCheck::make()->getRoutes(),
            Authentication::make()->getRoutes(),
            Charts::make()->getRoutes(),
            ScopeInvalidation::make()->getRoutes(),
            Listing::make()->getRoutes(),
            Store::make()->getRoutes(),
            Count::make()->getRoutes(),
            Show::make()->getRoutes(),
            Update::make()->getRoutes(),
            Destroy::make()->getRoutes(),
            ListingRelated::make()->getRoutes(),
            UpdateRelated::make()->getRoutes(),
            AssociateRelated::make()->getRoutes(),
            DissociateRelated::make()->getRoutes(),
            CountRelated::make()->getRoutes(),
        );
    }

    private static function getActionsRoutes(): array
    {
        $routes = [];
        foreach (AgentFactory::get('datasource')->getCollections() as $collection) {
            foreach ($collection->getActions() as $actionName => $action) {
                $routes[] = (new Actions($collection, $actionName))->getRoutes();
            }
        }

        return array_merge(...$routes);
    }

    private static function getApiChartsRoutes(): array
    {
        $routes = [];
        foreach (AgentFactory::get('datasource')->getCharts() as $chart) {
            $routes[] = (new ApiChartDatasource($chart))->getRoutes();
        }

        foreach (AgentFactory::get('datasource')->getCollections() as $collection) {
            foreach ($collection->getCharts() as $chart) {
                $routes[] = (new ApiChartCollection($collection, $chart))->getRoutes();
            }
        }

        return array_merge(...$routes);
    }
}
