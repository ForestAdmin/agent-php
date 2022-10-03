<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

function dataSourceWithInverseRelationMissing(): Datasource
{
    $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'       => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'author'   => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'persons',
                inverseRelationName: 'books'
            ),
            'authorId' => new ColumnSchema(
                columnType: PrimitiveType::UUID,
            ),
        ]
    );
    $collectionPersons = new Collection($datasource, 'persons');
    $collectionPersons->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
        ]
    );
    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionPersons);

    $options = [
        'projectDir' => sys_get_temp_dir(), // only use for cache
    ];
    (new AgentFactory($options,  []))->addDatasources([$datasource]);

    return $datasource;
}

function datasourceWithAllRelations(): Datasource
{
    $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'       => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'myPersons'   => new ManyToManySchema(
                originKey: 'bookId',
                originKeyTarget: 'id',
                throughTable: 'bookPersons',
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                foreignCollection: 'persons',
                inverseRelationName: 'persons'
            ),
            'myBookPersons'   => new OneToManySchema(
                originKey: 'bookId',
                originKeyTarget: 'id',
                foreignCollection: 'bookPersons',
                inverseRelationName: 'bookPersons'
            ),
        ]
    );
    $collectionBookPersons = new Collection($datasource, 'bookPersons');
    $collectionBookPersons->addFields(
        [
            'bookId'       => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'personId'       => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'myBook'   => new ManyToOneSchema(
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                foreignCollection: 'books',
                inverseRelationName: 'book'
            ),
            'myPerson'   => new ManyToOneSchema(
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                foreignCollection: 'persons',
                inverseRelationName: 'person'
            ),
        ]
    );
    $collectionPersons = new Collection($datasource, 'persons');
    $collectionPersons->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'name'      => new ColumnSchema(columnType: PrimitiveType::STRING),
            'myBooks'   => new ManyToManySchema(
                originKey: 'personId',
                originKeyTarget: 'id',
                throughTable: 'bookPersons',
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                foreignCollection: 'books',
                inverseRelationName: 'persons'
            ),
            'myBookPerson'   => new OneToOneSchema(
                originKey: 'personId',
                originKeyTarget: 'id',
                foreignCollection: 'bookPersons',
                inverseRelationName: 'bookPersons'
            ),
        ]
    );
    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionBookPersons);
    $datasource->addCollection($collectionPersons);

    $options = [
        'projectDir' => sys_get_temp_dir(), // only use for cache
    ];
    (new AgentFactory($options,  []))->addDatasources([$datasource]);

    return $datasource;
}

test('getInverseRelation() should not find an inverse when inverse relations is missing', function () {
    $datasource = dataSourceWithInverseRelationMissing();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('books'), 'author'))->toBeNull();
});

test('getInverseRelation() should inverse a one to many relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('books'), 'myBookPersons'))->toEqual('myBook')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('bookPersons'), 'myBook'))->toEqual('myBookPersons');
});

test('getInverseRelation() should inverse a many to many relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('books'), 'myPersons'))->toEqual('myBooks')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('persons'), 'myBooks'))->toEqual('myPersons');
});

test('getInverseRelation() should inverse a one to one relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('persons'), 'myBookPerson'))->toEqual('myPerson')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('bookPersons'), 'myPerson'))->toEqual('myBookPerson');
});

test('isManyToManyInverse() should return false', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBooks */
    $collectionBooks = $datasource->getCollection('books');
    $manyToManyRelation = new ManyToManySchema(
        originKey: 'fooId',
        originKeyTarget: 'id',
        throughTable: 'bookFoo',
        foreignKey: 'bookId',
        foreignKeyTarget: 'id',
        foreignCollection: 'books',
        inverseRelationName: 'persons'
    );

    expect(CollectionUtils::isManyToManyInverse($collectionBooks->getFields()['myPersons'], $manyToManyRelation))->toBeFalse();
});

test('isManyToManyInverse() should return true', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBooks */
    $collectionBooks = $datasource->getCollection('books');
    $collectionPersons = $datasource->getCollection('persons');

    expect(CollectionUtils::isManyToManyInverse($collectionBooks->getFields()['myPersons'], $collectionPersons->getFields()['myBooks']))->toBeTrue();
});

test('isManyToOneInverse() should return false', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBooks */
    $collectionBookPersons = $datasource->getCollection('bookPersons');
    $collectionPersons = $datasource->getCollection('persons');

    expect(CollectionUtils::isManyToOneInverse($collectionBookPersons->getFields()['myBook'], $collectionPersons->getFields()['myBookPerson']))->toBeFalse();
});

test('isManyToOneInverse() should return true', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBooks */
    $collectionBookPersons = $datasource->getCollection('bookPersons');
    $collectionBooks = $datasource->getCollection('books');

    expect(CollectionUtils::isManyToOneInverse($collectionBookPersons->getFields()['myBook'], $collectionBooks->getFields()['myBookPersons']))->toBeTrue();
});

test('isOtherInverse() should return false', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBooks */
    $collectionBookPersons = $datasource->getCollection('bookPersons');
    $collectionPersons = $datasource->getCollection('persons');

    expect(CollectionUtils::isOtherInverse($collectionPersons->getFields()['myBookPerson'], $collectionBookPersons->getFields()['myBook']))->toBeFalse();
});

test('isOtherInverse() should return true', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBooks */
    $collectionBookPersons = $datasource->getCollection('bookPersons');
    $collectionPersons = $datasource->getCollection('persons');

    expect(CollectionUtils::isOtherInverse($collectionPersons->getFields()['myBookPerson'], $collectionBookPersons->getFields()['myPerson']))->toBeTrue();
});

test('getFieldSchema() should throw with unknown column', function () {
    $datasource = datasourceWithAllRelations();
    $collectionPersons = $datasource->getCollection('persons');

    expect(static fn () => CollectionUtils::getFieldSchema($collectionPersons, 'foo'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Column not found persons.foo');
});

test('getFieldSchema() should work with simple column', function () {
    $datasource = datasourceWithAllRelations();
    $collectionPersons = $datasource->getCollection('persons');

    expect(CollectionUtils::getFieldSchema($collectionPersons, 'name'))->toEqual(
        new ColumnSchema(columnType: PrimitiveType::STRING)
    );
});

test('getFieldSchema() should throw with unknown relation:column', function () {
    $datasource = datasourceWithAllRelations();
    $collectionPersons = $datasource->getCollection('persons');

    expect(static fn () => CollectionUtils::getFieldSchema($collectionPersons, 'unknown:foo'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Relation not found persons.unknown');
});

test('getFieldSchema() should throw with invalid relation type', function () {
    $datasource = datasourceWithAllRelations();
    $collectionBooks = $datasource->getCollection('books');

    expect(static fn () => CollectionUtils::getFieldSchema($collectionBooks, 'myBookPersons:bookId'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Unexpected field type OneToMany: books.myBookPersons');
});

test('getFieldSchema() should work with relation column', function () {
    $datasource = datasourceWithAllRelations();
    $collectionBookPersons = $datasource->getCollection('bookPersons');

    expect(CollectionUtils::getFieldSchema($collectionBookPersons, 'myPerson:name'))->toEqual(
        new ColumnSchema(columnType: PrimitiveType::STRING)
    );
});

test('getThroughTarget() should throw with invalid relation type', function () {
    $datasource = datasourceWithAllRelations();
    $collectionBooks = $datasource->getCollection('books');

    expect(static fn () => CollectionUtils::getThroughTarget($collectionBooks, 'myBookPersons'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Relation must be many to man');
});

test('getThroughTarget() should work', function () {
    $datasource = datasourceWithAllRelations();
    $collectionBooks = $datasource->getCollection('books');

    expect(CollectionUtils::getThroughTarget($collectionBooks, 'myPersons'))
        ->toEqual('persons');
});
