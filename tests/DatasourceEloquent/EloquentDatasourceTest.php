<?php

use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\Tests\TestCase;

beforeEach(closure: function () {
    $this->buildAgent(new Datasource(), ['projectDir' => __DIR__]);
    $this->initDatabase();
});

it('can instantiate EloquentDatasource with valid configurations', function () {
    $eloquentDatasource = new EloquentDatasource(TestCase::DB_CONFIG, 'eloquent_collection', true);

    expect($eloquentDatasource)->toBeInstanceOf(EloquentDatasource::class)
        ->and($eloquentDatasource->getModels())->toBeArray();
});

it('throws exception for unknown live query connection', function () {
    $datasource = new EloquentDatasource(['driver' => 'mysql'], false, ['customConnection']);

    $datasource->executeNativeQuery('unknown_connection', 'SELECT * FROM users');
})->throws(ForestException::class, "Native query connection 'unknown_connection' is unknown.");

it('returns correct live query connections', function () {
    $liveQueryConnections = ['connection1' => 'pgsql', 'connection2' => 'mysql'];
    $datasource = new EloquentDatasource(['driver' => 'mysql'], false, $liveQueryConnections);

    expect($datasource->getLiveQueryConnections())->toBe($liveQueryConnections);
});

it('serializes and unserializes correctly', function () {
    $datasource = new EloquentDatasource(['driver' => 'mysql'], true, ['connection1']);

    $serialized = serialize($datasource);
    $unserializedDatasource = unserialize($serialized);

    expect($unserializedDatasource)->toBeInstanceOf(EloquentDatasource::class)
        ->and($unserializedDatasource->getLiveQueryConnections())->toBe(['connection1'])
        ->and($unserializedDatasource->getModels())->toBeArray();
});
