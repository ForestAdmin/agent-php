<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

test('make() should return a new instance of HealthCheck with routes', function () {
    $healthCheck = HealthCheck::make();

    expect($healthCheck)->toBeInstanceOf(HealthCheck::class)
        ->and($healthCheck->getRoutes())->toHaveKey('forest');
});

test('handleRequest() should return a response 200', function () {
    $datasource = new Datasource();
    $this->buildAgent($datasource);

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

    expect($healthCheck->handleRequest())
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});
