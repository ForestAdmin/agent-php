<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;

test('getPrimaryKeys() should find the pks', function () {
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addFields(
        [
            'id'      => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'notId'   => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
            'otherId' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
        ]
    );
    $result = SchemaUtils::getPrimaryKeys($collection);

    expect($result)->toBeArray()
        ->and($result)->toEqual(['id', 'otherId']);
});

test('getToManyRelation() should find relations ManyToMany and OneToMany', function () {
    $collection = new Collection(new Datasource(), 'Book');
    $manyToMany = new ManyToManySchema(
        originKey: 'book_id',
        originKeyTarget: 'id',
        throughTable: 'bookReview',
        foreignKey: 'review_id',
        foreignKeyTarget: 'id',
        foreignCollection: 'Review',
        inverseRelationName: 'books',
    );
    $oneToMany = new OneToManySchema(
        originKey: 'book_id',
        originKeyTarget: 'id',
        foreignCollection: 'Review',
        inverseRelationName: 'books'
    );
    $collection->addFields(
        [
            'id'                 => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'relationManyToMany' => $manyToMany,
            'relationOneToMany'  => $oneToMany,
        ]
    );

    expect(SchemaUtils::getToManyRelation($collection, 'relationManyToMany'))->toEqual($manyToMany)
    ->and(SchemaUtils::getToManyRelation($collection, 'relationOneToMany'))->toEqual($oneToMany);
});

test('getToManyRelation() should throw if relation not exist', function () {
    $collection = new Collection(new Datasource(), 'Book');
    $collection->addFields(
        [
            'id'                 => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'relationOneToMany'  => new OneToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'Review',
                inverseRelationName: 'books'
            ),
        ]
    );

    expect(static fn () => SchemaUtils::getToManyRelation($collection, 'relationFoo'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Relation relationFoo not found');
});

test('getToManyRelation() should throw if relation is not ManyToMany or OneToMany', function () {
    $collection = new Collection(new Datasource(), 'Book');
    $collection->addFields(
        [
            'id'                 => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'relationManyToOne'  => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
                inverseRelationName: 'books'
            ),
        ]
    );

    expect(static fn () => SchemaUtils::getToManyRelation($collection, 'relationManyToOne'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Relation relationManyToOne has invalid type should be one of OneToMany or ManyToMany.');
});
