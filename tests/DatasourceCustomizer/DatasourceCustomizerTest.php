<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factoryDatasourceCustomizer()
{
    $datasource = new Datasource();
    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $collectionCategory = new Collection($datasource, 'Category');
    $collectionCategory->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'label' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $datasource->addCollection($collectionPerson);
    $datasource->addCollection($collectionCategory);
    buildAgent($datasource);

    return $datasource;
}

test('addDatasource() should return the collection datasource', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();
    $datasourceCustomizer->addDatasource($datasource);

    expect($datasourceCustomizer->getStack()->dataSource->getCollection('Person')->getName())->toEqual('Person')
        ->and($datasourceCustomizer->getStack()->dataSource->getCollection('Category')->getName())->toEqual('Category');
});

test('addDatasource() should hide collections', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();
    $datasourceCustomizer->addDatasource($datasource, ['exclude' => ['Category']]);

    expect($datasourceCustomizer->getStack()->dataSource->getCollections()->count())->toEqual(1);
});

test('addaDatasource() exclude should throw an error when the collection is unknown', closure: function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();

    expect(fn () => $datasourceCustomizer->addDatasource($datasource, ['exclude' => ['Foo']]))->toThrow(ForestException::class, '🌳🌳🌳 Unknown collection name: Foo');
});

test('addDatasource() should add only a specific collections', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();
    $datasourceCustomizer->addDatasource($datasource, ['include' => ['Category']]);

    expect($datasourceCustomizer->getStack()->dataSource->getCollections()->count())->toEqual(1);
});

test('addaDatasource() include should throw an error when the collection is unknown', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();

    expect(fn () => $datasourceCustomizer->addDatasource($datasource, ['include' => ['Foo']]))->toThrow(ForestException::class, '🌳🌳🌳 Unknown collection name: Foo');
});

test('addDatasource() should rename a collection without errors', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();
    $datasourceCustomizer->addDatasource($datasource, ['rename' => ['Category' => 'MyCategory']]);

    expect($datasourceCustomizer->getStack()->dataSource->getCollections()->keys())->toEqual(collect(['Person', 'MyCategory']));
});

test('addaDatasource() rename should throw an error when the collection is unknown', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();

    expect(fn () => $datasourceCustomizer->addDatasource($datasource, ['rename' => ['Foo' => 'Bar']]))
        ->toThrow(ForestException::class, '🌳🌳🌳 The given collection name Foo does not exist');
});

test('customizeCollection() should throw an error when designed collection is unknown', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();
    $datasourceCustomizer->addDatasource($datasource);

    expect(fn () => $datasourceCustomizer->customizeCollection('Foo', function (CollectionCustomizer $builder) {
        $builder->replaceFieldSorting('label', null);
    }))->toThrow(ForestException::class, '🌳🌳🌳 Collection Foo not found');
});

test('customizeCollection() should provide collection customizer otherwise', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();
    $datasourceCustomizer->addDatasource($datasource);
    $datasourceCustomizer->customizeCollection('Category', function (CollectionCustomizer $builder) {
        $builder->replaceFieldSorting('label', null);
    });

    expect($datasourceCustomizer->getStack()->dataSource->getCollection('Person')->getName())->toEqual('Person')
        ->and($datasourceCustomizer->getStack()->dataSource->getCollection('Category')->getName())->toEqual('Category');
});

test('use() should add a plugin', function () {
    $datasource = factoryDatasourceCustomizer();
    $datasourceCustomizer = new DatasourceCustomizer();
    $datasourceCustomizer->addDatasource($datasource);

    mock('overload:MyFakePlugin')
        ->expects('run')
        ->getMock();

    $datasourceCustomizer->use('MyFakePlugin');
});
