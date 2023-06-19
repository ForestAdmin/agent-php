<?php

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

use function ForestAdmin\config;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function factoryForestApiRequester($url = 'https://api.development.forestadmin.com'): void
{
    $datasource = new Datasource();
    buildAgent($datasource, ['debug' => false, 'forestServerUrl' => $url]);
}

function mockClientResponse(): Client
{
    $mock = new MockHandler([new Response(200, ['forest-secret-key' => AUTH_SECRET], 'ok'),]);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}

function mockClientResponseException(): Client
{
    $mock = new MockHandler([new \RuntimeException('Cannot reach Forest API at ' . config('forestServerUrl') . '/foo, it seems to be down right now'),]);
    $handlerStack = HandlerStack::create($mock);

    return new Client(['handler' => $handlerStack]);
}

test('getHeaders() should work', function () {
    buildAgent(new Datasource());
    factoryForestApiRequester();
    $forestApi = new ForestApiRequester();
    $forestApi->setClient(mockClientResponse());

    expect($forestApi->getHeaders())
        ->toEqual(
            [
                'Content-Type'      => 'application/json',
                'forest-secret-key' => SECRET,
            ]
        );
});

test('get() should return a Response with a 200 status code', function () {
    factoryForestApiRequester();
    $forestApi = new ForestApiRequester();
    $forestApi->setClient(mockClientResponse());

    $response = $forestApi->get('/foo');

    expect($response)
        ->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())
        ->toEqual(200)
        ->and($response->getHeaders()['forest-secret-key'][0])
        ->toEqual(AUTH_SECRET);
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
        ->and($response->getHeaders()['forest-secret-key'][0])
        ->toEqual(AUTH_SECRET);
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
