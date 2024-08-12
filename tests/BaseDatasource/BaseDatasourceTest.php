<?php

use Doctrine\DBAL\Connection;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
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
