<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

use function ForestAdmin\config;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->buildAgent(
        new Datasource(),
        [
            'debug'           => false,
            'forestServerUrl' => 'https://api.development.forestadmin.com',
        ]
    );
});

describe('Client return a success response', function () {
    $mockClient = static function (): Client {
        $mock = new MockHandler([new Response(200, ['forest-secret-key' => AUTH_SECRET], 'ok'),]);
        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    };

    test('getHeaders() should work', function () use ($mockClient) {
        $forestApi = new ForestApiRequester();
        $forestApi->setClient($mockClient());

        expect($forestApi->getHeaders())
            ->toEqual(
                [
                    'Content-Type'      => 'application/json',
                    'forest-secret-key' => SECRET,
                ]
            );
    });

    test('get() should return a Response with a 200 status code', function () use ($mockClient) {
        $forestApi = new ForestApiRequester();
        $forestApi->setClient($mockClient());

        $response = $forestApi->get('/foo');

        expect($response)
            ->toBeInstanceOf(Response::class)
            ->and($response->getStatusCode())
            ->toEqual(200)
            ->and($response->getHeaders()['forest-secret-key'][0])
            ->toEqual(AUTH_SECRET);
    });

    test('post() should return a Response with a 200 status code', function () use ($mockClient) {
        $forestApi = new ForestApiRequester();
        $forestApi->setClient($mockClient());

        $response = $forestApi->post('/foo', [], ['key' => 'value']);

        expect($response)
            ->toBeInstanceOf(Response::class)
            ->and($response->getStatusCode())
            ->toEqual(200)
            ->and($response->getHeaders()['forest-secret-key'][0])
            ->toEqual(AUTH_SECRET);
    });

    test('validateUrl() should return an Exception when the url is not correctly formatted', function () use ($mockClient) {
        $config = Cache::get('config');
        $config['forestServerUrl'] = 'bad-url';
        Cache::put('config', $config, 60);
        $forestApi = new ForestApiRequester();
        $forestApi->setClient($mockClient());

        expect(fn () => $forestApi->get('/foo'))
            ->toThrow(\ErrorException::class, 'bad-url/foo seems to be an invalid url');
    });
});

describe('Client return a error response', function () {
    $mockClient = static function (): Client {
        $mock = new MockHandler([new \RuntimeException('Cannot reach Forest API at ' . config('forestServerUrl') . '/foo, it seems to be down right now'),]);
        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    };

    test('get() should throw a exception when the Response can\'t reach the server', function () use ($mockClient) {
        $forestApi = new ForestApiRequester();
        $forestApi->setClient($mockClient());

        expect(fn () => $forestApi->get('/foo'))
            ->toThrow(\ErrorException::class, 'Cannot reach Forest API at ' . config('forestServerUrl') . '/foo, it seems to be down right now');
    });

    test('post() should throw a exception when the Response can\'t reach the server', function () use ($mockClient) {
        $forestApi = new ForestApiRequester();
        $forestApi->setClient($mockClient());

        expect(fn () => $forestApi->post('/foo', [], ['key' => 'value']))
            ->toThrow(\ErrorException::class, 'Cannot reach Forest API at ' . config('forestServerUrl') . '/foo, it seems to be down right now');
    });
});
