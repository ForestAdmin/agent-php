<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\System;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Routing\RouteCollector;

class HealthCheck extends AbstractRoute
{
    public function __construct(protected ForestAdminHttpDriverServices $services, protected array $options)
    {
        parent::__construct($services, $options);
    }

    public function setupRoutes(RouteCollector $router): void
    {
        $router->map(
            ['GET'],
            '',
            fn (Request $request, Response $response) => $this->handleRequest($request, $response)
        );
    }

    public function handleRequest(Request $request, Response $response)
    {
        $payload = json_encode([
            'error'   => null,
            'message' => 'Agent is running',
        ], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}
