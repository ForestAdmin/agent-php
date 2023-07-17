<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

beforeEach(fn () => $this->buildAgent(new Datasource()));
afterEach(fn () => Cache::forget('foo'));

test('put() should save into cache & get() should return the value of the key', function () {
    Cache::put('foo', 'value', 2);

    expect(Cache::get('foo'))->toEqual('value');
});

test('remember should save cache and return the value of the key', function () {
    Cache::remember('foo', fn () => 'value', 2);

    expect(Cache::get('foo'))->toEqual('value');
});

test('forgot should remove a cache key', function () {
    Cache::put('foo', 'value', 2);
    Cache::forget('foo');

    expect(Cache::get('foo'))->toBeNull();
});
