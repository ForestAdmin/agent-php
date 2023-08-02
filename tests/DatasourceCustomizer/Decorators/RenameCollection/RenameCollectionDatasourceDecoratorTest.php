<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection\RenameCollectionDatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection\RenameCollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

\Ozzie\Nest\describe('RenameCollectionDatasourceDecorator', function () {
    beforeEach(function () {
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
        $this->buildAgent($datasource);

        $this->bucket = compact('datasource');
    });
});

test('replaceSearch() should return the real name when it is not renamed', function () {
    $datasource = $this->bucket['datasource'];
    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();

    expect($decoratedDataSource->getCollection('Person')->getName())->toEqual('Person');
});

test('renameCollections() should rename a collection when the rename option is given', function () {
    $datasource = $this->bucket['datasource'];
    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();
    $decoratedDataSource->renameCollections(['Person' => 'User']);

    expect(fn () => $decoratedDataSource->getCollection('Person'))->toThrow(ForestException::class, "🌳🌳🌳 Collection 'Person' has been renamed to 'User'")
        ->and($decoratedDataSource->getCollection('User'))->toBeInstanceOf(RenameCollectionDecorator::class);
});

test('renameCollections() should throw an error if the given new name is already used', function () {
    $datasource = $this->bucket['datasource'];
    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();

    expect(fn () => $decoratedDataSource->renameCollections(['Person' => 'Book']))
        ->toThrow(ForestException::class, '🌳🌳🌳 The given new collection name Book is already defined in the dataSource');
});

test('renameCollections() should throw an error if the given old name does not exist', function () {
    $datasource = $this->bucket['datasource'];
    $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
    $decoratedDataSource->build();

    expect(fn () => $decoratedDataSource->renameCollections(['Foo' => 'Bar']))
        ->toThrow(ForestException::class, '🌳🌳🌳 Collection Foo not found.');
});
