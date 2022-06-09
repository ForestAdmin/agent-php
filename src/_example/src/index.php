<?php

require __DIR__ . '/../../../vendor/autoload.php';
require 'register.php';

use ForestAdmin\AgentPHP\Agent\Builder\Agent;
use ForestAdmin\AgentPHP\DatasourceDummy\DummyDatasource;

function makeAgent(): Agent
{
    $options = [
        'authSecret'      => '',
        'agentUrl'        => 'http://localhost:8000',
        'envSecret'       => '',
        'forestServerUrl' => 'https://api.development.forestadmin.com',
        'isProduction'    => false,
        'loggerLevel'     => 'Info',
        'prefix'          => 'forest',
        'schemaPath'      => __DIR__ . '/.forestadmin-schema.json',
    ];
    $agent = new Agent($options);
    $dummyDatasource = new DummyDatasource();


    return $agent->addDatasource($dummyDatasource);
}

$agent = makeAgent()->mountOnStandaloneServer();

$agent->start();
