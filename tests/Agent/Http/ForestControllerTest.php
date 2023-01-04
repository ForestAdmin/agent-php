<?php

use ForestAdmin\AgentPHP\Agent\Http\ForestController;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

test('invoke() should return a Response', function () {
    buildAgent(new Datasource());

    $_GET['_route'] = 'forest';
    $_GET['_route_params'] = [];

    $forestController = mock(new ForestController())
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('response')
        ->with([
            'content' => 'foo',
        ])
        ->getMock();

    $request = Request::createFromGlobals();

    expect($forestController($request))
        ->toBeInstanceOf(JsonResponse::class);
});

test('response() with the content-type "text/csv" should return a Response', function () {
    buildAgent(new Datasource());

    $_GET['_route'] = 'forest';
    $_GET['_route_params'] = [];

    $forestController = new ForestController();
    $data = [
        'content' => 'foo',
        'headers' => [
            'Content-type' => 'text/csv',
        ],
    ];

    expect(invokeMethod($forestController, 'response', [$data]))
        ->toBeInstanceOf(Response::class);
});

test('response() should return a JsonResponse', function () {
    buildAgent(new Datasource());

    $_GET['_route'] = 'forest';
    $_GET['_route_params'] = [];

    $forestController = new ForestController();
    $data = ['content' => 'foo'];

    expect(invokeMethod($forestController, 'response', [$data]))
        ->toBeInstanceOf(Response::class);
});
