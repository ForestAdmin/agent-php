<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection\RenameCollectionDatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

function factoryRenameCollectionDatasourceDecoratorCollection()
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

    return $datasource;
}

test('replaceSearch() should return the real name when it is not renamed', function () {
    $datasource = factoryRenameCollectionDatasourceDecoratorCollection();
    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();

    expect($decoratedDataSource->getCollection('Person')->getName())->toEqual('Person');
});

test('renameCollections() should rename a collection when the rename option is given', function () {
    $datasource = factoryRenameCollectionDatasourceDecoratorCollection();
    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();
    $decoratedDataSource->renameCollections(['Person' => 'User']);

    $collectionsKeys = array_keys($decoratedDataSource->getCollections()->toArray());

    expect(in_array('Person', $collectionsKeys, true))->toBeFalse()
        ->and(in_array('User', $collectionsKeys, true))->toBeTrue();
});

test('renameCollections() should throw an error if the given new name is already used', function () {
    $datasource = factoryRenameCollectionDatasourceDecoratorCollection();
    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();

    expect(fn () => $decoratedDataSource->renameCollections(['Person' => 'Book']))
        ->toThrow(ForestException::class, '🌳🌳🌳 The given new collection name Book is already defined in the dataSource');
});

test('renameCollections() should throw an error if the given old name does not exist', function () {
    $datasource = factoryRenameCollectionDatasourceDecoratorCollection();
    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();

    expect(fn () => $decoratedDataSource->renameCollections(['Foo' => 'Bar']))
        ->toThrow(ForestException::class, '🌳🌳🌳 The given collection name Foo does not exist');
});

//    it('should throw an error if the given new name is already used', () => {
//      const dataSource = setupWithManyToManyRelation();
//
//      expect(() => dataSource.renameCollection('librariesBooks', 'books')).toThrow(
//        'The given new collection name "books" is already defined in the dataSource',
//      );
//    });
//
//    it('should throw an error if the given old name does not exist', () => {
//      const dataSource = setupWithManyToManyRelation();
//
//      expect(() => dataSource.renameCollection('doesNotExist', 'books')).toThrow(
//        'The given collection name "doesNotExist" does not exist',
//      );
//    });
