<?php

use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\QueryAggregate;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\Tests\TestCase;

beforeEach(function () {
    global $datasource, $bookCollection;
    $this->buildAgent(new Datasource(), ['projectDir' => str_replace('/Utils', '', __DIR__)]);
    $this->initDatabase();
    $datasource = new EloquentDatasource(TestCase::DB_CONFIG);

    $bookCollection = $datasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');
});

test('of() should return a ForestAdmin\\AgentPHP\\DatasourceEloquent\\Utils\\QueryAggregate instance', function () {
    global $bookCollection;
    $query = QueryAggregate::of($bookCollection, 'Europe/Paris', new Aggregation('Count'));

    expect($query)->toBeInstanceOf(QueryAggregate::class);
});

test('get() should return a array of array with key value and group', function () {
    global $bookCollection;
    $query = QueryAggregate::of($bookCollection, 'Europe/Paris', new Aggregation('Count'));

    expect($query->get())->toEqual([['value' => 4, 'group' => []]]);
});

test('get() should work with grouped aggregation', function () {
    global $bookCollection;
    $aggregation = new Aggregation('Sum', 'price', [['field' => 'author_id']]);
    $query = QueryAggregate::of($bookCollection, 'Europe/Paris', $aggregation);

    expect($query->get())->toEqual(
        [
            ['value' => 20, 'group' => ['author_id' => 1]],
            ['value' => 10, 'group' => ['author_id' => 3]],
            ['value' => 10, 'group' => ['author_id' => 2]],
        ]
    );
});
