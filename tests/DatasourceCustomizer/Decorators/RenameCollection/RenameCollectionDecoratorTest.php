<?php

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BaseTransformer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection\RenameCollectionDatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

function factoryRenameCollectionDecoratorCollection()
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'authorId'     => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
            'author'       => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
            ),
        ]
    );

    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'book'         => new OneToOneSchema(
                originKey: 'authorId',
                originKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
        ]
    );

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionPerson);
    buildAgent($datasource);

    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();

    return $decoratedDataSource;
}

test('getName() should return the default collection name when there is no rename action', function () {
    $decoratedDataSource = factoryRenameCollectionDecoratorCollection();

    expect($decoratedDataSource->getCollection('Person')->getName())->toEqual('Person');
});

test('getName() should return the new name when there is a rename action', function () {
    $decoratedDataSource = factoryRenameCollectionDecoratorCollection();
    $decoratedDataSource->renameCollections(['Person' => 'User']);

    expect($decoratedDataSource->getCollection('User')->getName())->toEqual('User');
});

test('getFields() should return the fields of the collection', function () {
    $decoratedDataSource = factoryRenameCollectionDecoratorCollection();
    $decoratedDataSource->renameCollections(['Person' => 'User']);

    expect($decoratedDataSource->getCollection('User')->getFields())->toHaveKeys(['id', 'book']);
});

test('makeTransformer() should return ', function () {
    $decoratedDataSource = factoryRenameCollectionDecoratorCollection();
    $decoratedDataSource->renameCollections(['Person' => 'User']);

    expect($decoratedDataSource->getCollection('User')->makeTransformer())->toBeInstanceOf(BaseTransformer::class);
});
