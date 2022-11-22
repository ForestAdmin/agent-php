<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\SortValidator;

test('validate() should throw', function () {
    $collection = new Collection(new Datasource(), 'books');
    $collection->addFields(
        [
            'id'     => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'author' => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'persons',
                inverseRelationName: 'books'
            ),
        ]
    );

    expect(static fn () => SortValidator::validate($collection, new Sort([['field' => 'foo', 'ascending' => true]])))
        ->toThrow(ForestException::class, "🌳🌳🌳 Column not found: books.foo");
});

test('validate() should not throw', function () {
    $collection = new Collection(new Datasource(), 'books');
    $collection->addFields(
        [
            'id'     => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'author' => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'persons',
                inverseRelationName: 'books'
            ),
        ]
    );

    expect(SortValidator::validate($collection, new Sort([['field' => 'id', 'ascending' => true]])))->toBeNull();
});
