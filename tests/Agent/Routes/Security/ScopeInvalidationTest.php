<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Security\ScopeInvalidation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\Tests\TestCase;

function factoryScopeInvalidation(TestCase $testCase): ScopeInvalidation
{
    $datasource = new Datasource();
    $testCase->buildAgent($datasource);

    $request = Request::createFromGlobals();
    $scopeInvalidation = mock(ScopeInvalidation::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    $testCase->invokeProperty($scopeInvalidation, 'request', $request);

    return $scopeInvalidation;
}

test('make() should return a new instance of ScopeInvalidation with routes', function () {
    $scopeInvalidation = ScopeInvalidation::make();

    expect($scopeInvalidation)->toBeInstanceOf(ScopeInvalidation::class)
        ->and($scopeInvalidation->getRoutes())->toHaveKey('forest.scope-invalidation');
});

test('handleRequest() should return a response 200', function () {
    $_GET['renderingId'] = 1;
    $scopeInvalidation = factoryScopeInvalidation($this);

    expect($scopeInvalidation->handleRequest())
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});
