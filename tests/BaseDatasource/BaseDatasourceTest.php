<?php

use Doctrine\DBAL\Connection;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\Tests\TestCase;
use Illuminate\Database\Capsule\Manager;

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

test('makeDotrineConnection() should throw an exception if the driver is unknown', function () {
    expect(
        static fn () => new BaseDatasource(['driver' => 'fake-driver', 'database' => 'database.sqlite'])
    )->toThrow(ForestException::class, "🌳🌳🌳 The given driver 'fake-driver' is unknown, only the following drivers are supported: pgsql, mariadb, mysql, sqlite, sqlsrv");

});
