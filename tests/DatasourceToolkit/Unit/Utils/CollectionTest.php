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
    (new AgentFactory($options))->addDatasources([$datasource]);

    return $datasource;
}

function datasourceWithAllRelations() {
    $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'       => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'myPersons'   => new ManyToManySchema(
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                throughTable: 'bookPersons',
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
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'myBooks'   => new ManyToManySchema(
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                throughTable: 'bookPersons',
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

    $options = [
        'projectDir' => sys_get_temp_dir(), // only use for cache
    ];
    (new AgentFactory($options))->addDatasources([$datasource]);

    return $datasource;
}

it('should not find an inverse when inverse relations is missing', function () {
    $datasource = dataSourceWithInverseRelationMissing();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('books'), 'author'))->toBeNull();
});

it('should inverse a one to many relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('books'), 'myBookPersons'))->toEqual('myBook')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('bookPersons'), 'myBook'))->toEqual('myBookPersons');
});

// todo fix errors
it('should inverse a many to many relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('books'), 'myPersons'))->toEqual('myBooks')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('persons'), 'myBooks'))->toEqual('myPersons');
});

it('should inverse a one to one relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('persons'), 'myBookPerson'))->toEqual('myPerson')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('bookPersons'), 'myPerson'))->toEqual('myBookPerson');
});







