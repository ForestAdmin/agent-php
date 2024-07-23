<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
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
        foreignKey: 'review_id',
        foreignKeyTarget: 'id',
        foreignCollection: 'Review',
        throughCollection: 'BookReview'
    );
    $oneToMany = new OneToManySchema(
        originKey: 'book_id',
        originKeyTarget: 'id',
        foreignCollection: 'Review',
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
            ),
        ]
    );

    expect(static fn () => SchemaUtils::getToManyRelation($collection, 'relationManyToOne'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Relation relationManyToOne has invalid type should be one of OneToMany, ManyToMany or PolymorphicOneToMany.');
});

test('isPrimaryKey() should work', function () {
    $collection = new Collection(new Datasource(), 'Book');
    $collection->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'notId'   => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    expect(SchemaUtils::isPrimaryKey($collection, 'id'))->toBeTrue();
    expect(SchemaUtils::isPrimaryKey($collection, 'notId'))->toBeFalse();
});

test('isForeignKey() should work', function () {
    $collection = new Collection(new Datasource(), 'Book');
    $collection->addFields(
        [
            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'notId'        => new ColumnSchema(columnType: PrimitiveType::STRING),
            'authorId'     => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
            'author'       => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
            ),
        ]
    );


    expect(SchemaUtils::isForeignKey($collection, 'id'))->toBeFalse();
    expect(SchemaUtils::isForeignKey($collection, 'notId'))->toBeFalse();
    expect(SchemaUtils::isForeignKey($collection, 'author'))->toBeFalse();
    expect(SchemaUtils::isForeignKey($collection, 'authorId'))->toBeTrue();
});
