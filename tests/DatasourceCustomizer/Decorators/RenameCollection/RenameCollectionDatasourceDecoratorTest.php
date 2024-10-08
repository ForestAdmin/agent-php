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
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToManySchema;

describe('RenameCollectionDatasourceDecorator', function () {
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
                'comments' => new PolymorphicOneToManySchema(
                    originKey: 'commentableId',
                    originKeyTarget: 'id',
                    foreignCollection: 'Comment',
                    originTypeField: 'commentableType',
                    originTypeValue: 'Book',
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

        $collectionComment = new Collection($datasource, 'Comment');
        $collectionComment->addFields(
            [
                'id'              => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'title'           => new ColumnSchema(columnType: PrimitiveType::STRING),
                'commentableId'   => new ColumnSchema(columnType: PrimitiveType::NUMBER),
                'commentableType' => new ColumnSchema(columnType: PrimitiveType::STRING),
                'commentable'     => new PolymorphicManyToOneSchema(
                    foreignKeyTypeField: 'commentableType',
                    foreignKey: 'commentableId',
                    foreignKeyTargets: [
                        'Book'   => 'id',
                    ],
                    foreignCollections: [
                        'Book',
                    ],
                ),]
        );


        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionPerson);
        $datasource->addCollection($collectionComment);
        $this->buildAgent($datasource);

        $this->bucket = compact('datasource');
    });

    test('replaceSearch() should return the real name when it is not renamed', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);

        expect($decoratedDataSource->getCollection('Person')->getName())->toEqual('Person');
    });

    test('renameCollections() should rename a collection when the rename option is given', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
        $decoratedDataSource->renameCollections(['Person' => 'User']);

        expect(fn () => $decoratedDataSource->getCollection('Person'))->toThrow(ForestException::class, "🌳🌳🌳 Collection 'Person' has been renamed to 'User'")
            ->and($decoratedDataSource->getCollection('User'))->toBeInstanceOf(RenameCollectionDecorator::class);
    });

    test('renameCollections() should throw an error if the given new name is already used', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);

        expect(fn () => $decoratedDataSource->renameCollections(['Person' => 'Book']))
            ->toThrow(ForestException::class, '🌳🌳🌳 The given new collection name Book is already defined in the dataSource');
    });

    test('renameCollections() should throw an error if the given old name does not exist', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);

        expect(fn () => $decoratedDataSource->renameCollections(['Foo' => 'Bar']))
            ->toThrow(ForestException::class, '🌳🌳🌳 Collection Foo not found.');
    });

    test('renameCollections() should thrown when renameCollections is called twice on the same collection', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);

        $decoratedDataSource->renameCollections(['Person' => 'User']);

        expect(fn () => $decoratedDataSource->renameCollections(['User' => 'User2']))
            ->toThrow(ForestException::class, "🌳🌳🌳 Cannot rename a collection twice: Person->User->User2");
    });

    test('renameCollections() should thrown when collection has polymorphic association', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);

        expect(fn () => $decoratedDataSource->renameCollections(['Book' => 'BookRenamed']))
            ->toThrow(ForestException::class, "🌳🌳🌳 Cannot rename collection Book because it's a target of a polymorphic relation 'Comment.commentable'");
    });
});
