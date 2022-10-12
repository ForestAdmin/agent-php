<?php


use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort\SortFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

test('getFields should work', function () {
    $sort = new Sort(['column1', '-column2']);

    expect($sort->getFields())->toEqual(
        [
            [
                'field' => 'column1',
                'order' => 'ASC',
            ],
            [
                'field' => 'column2',
                'order' => 'DESC',
            ],
        ]
    );
});

test('nest should work', function () {
    $sort = new Sort(['column1', '-column2']);

    expect($sort->nest('prefix')->getFields())->toEqual(
        [
            [
                'field' => 'prefix:column1',
                'order' => 'ASC',
            ],
            [
                'field' => 'prefix:column2',
                'order' => 'DESC',
            ],
        ]
    );
});

test('fieldIsAscending should return true with ascending field', function () {
    $sort = new Sort(['field']);

    expect($sort->fieldIsAscending('field'))->toBeTrue();
});

test('fieldIsAscending should return false with descending field', function () {
    $sort = new Sort(['field']);

    expect($sort->fieldIsAscending('-field'))->toBeFalse();
});

test('SortFactory::byPrimaryKeys should work', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    expect(SortFactory::byPrimaryKeys($collection))->toEqual(new Sort(['id']));
});
