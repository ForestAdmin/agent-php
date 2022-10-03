<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

function factoryHealthCheck($args = []): HealthCheck
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'Europe/Paris';

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasources([$datasource]);


    Cache::put(
        'config',
        [
            'prefix'     => 'forest',
            'schemaPath' => sys_get_temp_dir() . '/.forestadmin-schema.json',
        ],
        300
    );

    $healthCheck = mock(HealthCheck::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('sendSchema')
        ->getMock();

    return $healthCheck;
}

test('make() should return a new instance of HealthCheck with routes', function () {
    $healthCheck = HealthCheck::make();

    expect($healthCheck)->toBeInstanceOf(HealthCheck::class)
        ->and($healthCheck->getRoutes())->toHaveKey('forest');
});

test('handleRequest() should return a response 200', function () {
    $healthCheck = factoryHealthCheck();

    expect($healthCheck->handleRequest())
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});
