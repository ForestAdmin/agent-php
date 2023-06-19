<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

function factoryHealthCheck($args = []): HealthCheck
{
    $datasource = new Datasource();
    buildAgent($datasource);

    Cache::put(
        'config',
        [
            'prefix'     => 'forest',
            'schemaPath' => sys_get_temp_dir() . '/.forestadmin-schema.json',
        ],
        300
    );

    return mock(HealthCheck::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('sendSchema')
        ->getMock();
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
