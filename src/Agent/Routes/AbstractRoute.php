<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;

abstract class AbstractRoute
{
    public function __construct(
        protected ForestAdminHttpDriverServices $services,
        protected array                         $options,
        protected array                         $routes = []
    ) {
    }

    public static function of(ForestAdminHttpDriverServices $services, array $options): self
    {
        return (new static($services, $options))->setupRoutes();
    }

    public function addRoute(string $name, string $method, string $uri, \Closure $closure): void
    {
        $this->routes[$name] = compact('method', 'uri', 'closure');
    }

    public function bootstrap(): void
    {
        // Do nothing by default -> maybe this function is not necessary in PHP context
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    abstract public function setupRoutes(): self;
}
