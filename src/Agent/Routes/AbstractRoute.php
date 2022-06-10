<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteCollectorProxy;

abstract class AbstractRoute
{
    public function __construct(protected ForestAdminHttpDriverServices $services, protected array $options)
    {
    }

    public function bootstrap(): void
    {
        // Do nothing by default -> maybe this function is not necessary in PHP context
    }

    abstract public function setupRoutes(RouteCollector $router): void;

//    abstract public function getType(RouterType $type);
}
