<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

function factoryCache()
{
    $datasource = new Datasource();
    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'schemaPath'   => sys_get_temp_dir() . '/.forestadmin-schema.json',
        'envSecret'    => SECRET,
        'isProduction' => false,
        'agentUrl'     => 'http://localhost/',
    ];
    (new AgentFactory($options, []))->addDatasources([$datasource]);
}

afterEach(fn () => Cache::forget('foo'));

test('put() should save into cache & get() should return the value of the key', function () {
    factoryCache();
    Cache::put('foo', 'value', 2);

    expect(Cache::get('foo'))->toEqual('value');
});

test('remember should save cache and return the value of the key', function () {
    factoryCache();
    Cache::remember('foo', fn () => 'value', 2);

    expect(Cache::get('foo'))->toEqual('value');
});

test('forgot should remove a cache key', function () {
    factoryCache();
    Cache::put('foo', 'value', 2);
    Cache::forget('foo');

    expect(Cache::get('foo'))->toBeNull();
});
