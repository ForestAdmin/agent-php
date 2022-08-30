<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Http\Request;

abstract class AbstractRoute
{
    protected Request $request;

    public function __construct(
        protected array $routes = []
    ) {
        $this->request = Request::createFromGlobals();
    }

    public function addRoute(string $name, array|string $methods, string $uri, \Closure $closure): void
    {
        $this->routes[$name] = compact('methods', 'uri', 'closure');
    }

    public function bootstrap(): void
    {
        // Do nothing by default -> maybe this function is not necessary in PHP context
    }

    public static function getRoutes(): array
    {
        return (new static())->setupRoutes()->routes;
    }

    abstract public function setupRoutes(): self;
}
