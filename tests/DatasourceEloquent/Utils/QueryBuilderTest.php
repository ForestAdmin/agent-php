<?php

use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\QueryBuilder;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function () {
    global $datasource;
    $this->buildAgent(new Datasource(), ['projectDir' => str_replace('/Utils', '', __DIR__)]);
    $this->initDatabase();
    $datasource = new EloquentDatasource(TestCase::DB_CONFIG);
});

test('of() should return a \\ForestAdmin\\AgentPHP\\DatasourceEloquent\\Utils\\QueryBuilder instance', function () {
    global $datasource;
    expect(QueryBuilder::of($datasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book')))->toBeInstanceOf(QueryBuilder::class);
});

test('getQuery() should return a Illuminate\\Database\\Eloquent\\Builder instance', function () {
    global $datasource;
    expect(QueryBuilder::of($datasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book'))->getQuery())->toBeInstanceOf(Builder::class);
});
