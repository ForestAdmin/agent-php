<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\System;

use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;

class HealthCheck extends AbstractRoute
{
    public function __construct(
        ForestAdminHttpDriverServices $services,
        array $options,
        ForestAdminHttpDriver $httpDriver
    ) {
        parent::__construct($services, $options, $httpDriver);
    }

    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
//        $this->addRoute(
//            'forest',
//            'get',
//            '/',
//            fn () => $this->handleRequest()
//        );

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
        /*dd($_SERVER);
        $server = collect($_SERVER)->only('REQUEST_METHOD', 'HTTP_HOST', 'REQUEST_URI');
        $server->put('QUERY_STRING', $_REQUEST);


        dd($server);
        return 'ok';
//        return forestResponse();*/
        $this->httpDriver->sendSchema();


        return [
            'status'  => 204,
            'content' => null,
        ];
    }
}
