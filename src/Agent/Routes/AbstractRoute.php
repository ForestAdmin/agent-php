<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use Slim\Interfaces\RouteInterface;

abstract class AbstractRoute
{
    public function __construct(protected ForestAdminHttpDriver $services, protected array $options)
    {
    }

    public function bootstrap(): void
    {
        // Do nothing by default -> maybe this function is not necessary in PHP context
    }

    abstract public function setupRoutes(RouteInterface $router): void;

    abstract public function getType(RouteInterface $router);
}
