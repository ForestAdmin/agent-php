<?php

use Doctrine\DBAL\Connection;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\Tests\TestCase;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Collection;

beforeEach(closure: function () {
    global $baseDatasource;
    $this->buildAgent(new Datasource(), ['projectDir' => __DIR__]);
    $this->initDatabase();
    $baseDatasource = new BaseDatasource(TestCase::DB_CONFIG);
});

test('makeOrm() should return a Manager object', function () {
    global $baseDatasource;
    $orm = $baseDatasource->getOrm();

    expect($orm)->toBeInstanceOf(Manager::class);
});

test('makeDoctrineConnection() should return a Connection object', function () {
    global $baseDatasource;
    $connection = $baseDatasource->getDoctrineConnection();

    expect($connection)->toBeInstanceOf(Connection::class);
});

test('makeDoctrineConnection() should throw an exception if the driver is unknown', function () {
    expect(
        static fn () => new BaseDatasource(['driver' => 'fake-driver', 'database' => 'database.sqlite'])
    )->toThrow(ForestException::class, "🌳🌳🌳 The given driver 'fake-driver' is unknown, only the following drivers are supported: pgsql, postgresql, mariadb, mysql, sqlite, sqlsrv");
});

test('makeDoctrineConnection() should sent the correct configuration to doctrineConnection', function () {
    global $baseDatasource;
    $databaseConfig = [
        'driver'   => 'pgsql',
        'host'     => 'localhost',
        'database' => 'test',
        'port'     => '5432',
        'username' => 'pguser',
        'password' => 'pgpass',
    ];
    $baseDatasource = new BaseDatasource($databaseConfig);
    $connection = $baseDatasource->getDoctrineConnection();

    expect($connection->getParams())->toBe([
        'user'      => $databaseConfig['username'],
        'password'  => $databaseConfig['password'],
        'host'      => $databaseConfig['host'],
        'dbname'    => $databaseConfig['database'],
        'port'      => $databaseConfig['port'],
        'driver'    => 'pdo_pgsql',
        'url'       => null,
    ]);
});

test('serialize() should return the correct serialized object', function () {
    global $baseDatasource;
    $serialized = $baseDatasource->__serialize();

    expect($serialized)
        ->toHaveKey('collections')
        ->toHaveKey('charts')
        ->toHaveKey('databaseConfig')
        ->and($serialized['databaseConfig'])->toBe(TestCase::DB_CONFIG);
});

test('unserialize() should return the correct object', function () {
    global $baseDatasource;
    $serialized = $baseDatasource->__serialize();
    $baseDatasource->__unserialize($serialized);

    expect($baseDatasource->getCollections())->toBeInstanceOf(Collection::class)
        ->and($baseDatasource->getCharts())->toBeInstanceOf(Collection::class)
        ->and($this->invokeProperty($baseDatasource, 'databaseConfig'))
        ->toBe(TestCase::DB_CONFIG);
});
