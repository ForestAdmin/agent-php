<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

dataset('dataSourceWithInverseRelationMissing', function () {
    yield $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'       => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'author'   => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'persons',
            ),
            'authorId' => new ColumnSchema(
                columnType: PrimitiveType::Uuid(),
            ),
        ]
    );
    $collectionPersons = new Collection($datasource, 'persons');
    $collectionPersons->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
        ]
    );
    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionPersons);
});

dataset('datasourceWithAllRelations', function () {
    yield $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'       => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'myPersons'   => new ManyToManySchema(
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                throughCollection: 'bookPersons',
                originKey: 'bookId',
                originKeyTarget: 'id',
                foreignCollection: 'persons',
            ),
            'myBookPersons'   => new OneToManySchema(
                originKey: 'bookId',
                originKeyTarget: 'id',
                foreignCollection: 'bookPersons',
            ),
        ]
    );
    $collectionBookPersons = new Collection($datasource, 'bookPersons');
    $collectionBookPersons->addFields(
        [
            'bookId'       => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'personId'       => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'myBook'   => new ManyToOneSchema(
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                foreignCollection: 'books',
            ),
            'myPerson'   => new ManyToOneSchema(
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                foreignCollection: 'persons',
            ),
        ]
    );
    $collectionPersons = new Collection($datasource, 'persons');
    $collectionPersons->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'myBooks'   => new ManyToManySchema(
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                throughCollection: 'bookPersons',
                originKey: 'personId',
                originKeyTarget: 'id',
                foreignCollection: 'books',
            ),
            'myBookPerson'   => new OneToOneSchema(
                originKey: 'personId',
                originKeyTarget: 'id',
                foreignCollection: 'bookPersons',
            ),
        ]
    );
    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionBookPersons);
    $datasource->addCollection($collectionPersons);
});

it('should not find an inverse', function ($datasource) {
    expect(CollectionUtils::getInverseRelation($datasource->getCollection('books'), 'author'))->toBeNull();
})->with('dataSourceWithInverseRelationMissing');
