<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use function ForestAdmin\config;

function factoryForestApiRequester($url = 'https://api.development.forestadmin.com'): void
{
    $datasource = new Datasource();

    $options = [
        'projectDir'      => sys_get_temp_dir(),
        'envSecret'       => SECRET,
        'isProduction'    => false,
        'debug'           => false,
        'forestServerUrl' => $url,
    ];
    (new AgentFactory($options, []))->addDatasources([$datasource]);
}

function mockClientResponse(): Client
{
    $mock = new MockHandler([new Response(200, ['forest-secret-key' => SECRET], 'ok'),]);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}

function mockClientResponseException(): Client
{
    $mock = new MockHandler([new \RuntimeException('Cannot reach Forest API at ' . config('forestServerUrl') . '/foo, it seems to be down right now'),]);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}

test('get() should return a Response with a 200 status code', function () {
    factoryForestApiRequester();
    $forestApi = new ForestApiRequester();
    $forestApi->setClient(mockClientResponse());

    $response = $forestApi->get('/foo');

    expect($response)
        ->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())
        ->toEqual(200)
        ->and($forestApi->getHeaders()['forest-secret-key'])
        ->toEqual(SECRET);
});

test('post() should return a Response with a 200 status code', function () {
    factoryForestApiRequester();
    $forestApi = new ForestApiRequester();
    $forestApi->setClient(mockClientResponse());

    $response = $forestApi->post('/foo', [], ['key' => 'value']);

    expect($response)
        ->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())
        ->toEqual(200)
        ->and($forestApi->getHeaders()['forest-secret-key'])
        ->toEqual(SECRET);
});

test('get() should throw a exception when the Response can\'t reach the server', function () {
    factoryForestApiRequester();
    $forestApi = new ForestApiRequester();
    $forestApi->setClient(mockClientResponseException());

    expect(fn () => $forestApi->get('/foo'))
        ->toThrow(\ErrorException::class, 'Cannot reach Forest API at ' . config('forestServerUrl') . '/foo, it seems to be down right now');
});

test('post() should throw a exception when the Response can\'t reach the server', function () {
    factoryForestApiRequester();
    $forestApi = new ForestApiRequester();
    $forestApi->setClient(mockClientResponseException());

    expect(fn () => $forestApi->post('/foo', [], ['key' => 'value']))
        ->toThrow(\ErrorException::class, 'Cannot reach Forest API at ' . config('forestServerUrl') . '/foo, it seems to be down right now');
});

test('validateUrl() should return an Exception when the url is not correctly formatted', function () {
    factoryForestApiRequester('bad-url');
    $forestApi = new ForestApiRequester();
    $forestApi->setClient(mockClientResponse());

    expect(fn () => $forestApi->get('/foo'))
        ->toThrow(\ErrorException::class, 'bad-url/foo seems to be an invalid url');
});