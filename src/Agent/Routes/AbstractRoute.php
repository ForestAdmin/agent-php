<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Http\Request;

abstract class AbstractRoute
{
    protected Request $request;

    protected array $routes = [];

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
    }

    public function addRoute(string $name, array|string $methods, string $uri, \Closure $closure): void
    {
        $this->routes[$name] = compact('methods', 'uri', 'closure');
    }

    public static function make(): self
    {
        return (new static())->setupRoutes();
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    abstract public function setupRoutes(): self;
}
