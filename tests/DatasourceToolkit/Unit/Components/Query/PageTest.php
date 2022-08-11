<?php


use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort\SortFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

dataset('sort', function () {
    yield $sort = new Sort(['column1', '-column2']);
});

test('getFields should work', function (Sort $sort) {
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
})->with('sort');

test('nest should work', function (Sort $sort) {
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
})->with('sort');

test('fieldIsAscending should return true with ascending field', function (Sort $sort) {
    expect($sort->fieldIsAscending('field'))->toBeTrue();
})->with('sort');

test('fieldIsAscending should return true with descending field', function (Sort $sort) {
    expect($sort->fieldIsAscending('-field'))->toBeFalse();
})->with('sort');

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
