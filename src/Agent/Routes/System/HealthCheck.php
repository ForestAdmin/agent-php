<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\System;

use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use function ForestAdmin\cache;
use function ForestAdmin\httpDriver;

class HealthCheck extends AbstractRoute
{
    public function __construct(
        ForestAdminHttpDriverServices $services,
    ) {
        parent::__construct($services);
    }

    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $this->addRoute(
            'forest',
            'get',
            '',
            fn () => $this->handleRequest()
        );

        return $this;
    }

    public function handleRequest()
    {
        $request = Request::createFromGlobals();

//        $collection = cache('datasource')->getCollection('Booking');
//        dd(QueryStringParser::parseConditionTree($collection, $request));


        ForestAdminHttpDriver::sendSchema(cache('datasource'));

        return new Response(null, 204);
    }
}
