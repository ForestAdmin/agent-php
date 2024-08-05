<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection\PublicationCollectionDatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToManySchema;

describe('PublicationCollection', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'authorId' => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
                'author'   => new ManyToOneSchema(
                    foreignKey: 'authorId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
                'persons'  => new ManyToManySchema(
                    originKey: 'bookId',
                    originKeyTarget: 'id',
                    foreignKey: 'personId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                    throughCollection: 'BookPerson',
                ),
            ]
        );

        $collectionBookPerson = new Collection($datasource, 'BookPerson');
        $collectionBookPerson->addFields(
            [
                'personId' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'bookId'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'book'     => new ManyToOneSchema(
                    foreignKey: 'bookId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Book',
                ),
                'person'   => new ManyToOneSchema(
                    foreignKey: 'personId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
            ]
        );

        $collectionPerson = new Collection($datasource, 'Person');
        $collectionPerson->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'book'  => new OneToOneSchema(
                    originKey: 'authorId',
                    originKeyTarget: 'id',
                    foreignCollection: 'Book',
                ),
                'books' => new ManyToManySchema(
                    originKey: 'personId',
                    originKeyTarget: 'id',
                    foreignKey: 'bookId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Book',
                    throughCollection: 'BookPerson',
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
                        'Person'   => 'id',
                    ],
                    foreignCollections: [
                        'Person',
                    ],
                ),]
        );

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionBookPerson);
        $datasource->addCollection($collectionPerson);
        $datasource->addCollection($collectionComment);
        $this->buildAgent($datasource);

        $this->bucket['datasource'] = $datasource;
    });

    test('should return all collections when no parameter is provided', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new PublicationCollectionDatasourceDecorator($datasource);
        $decoratedDataSource->build();

        expect($decoratedDataSource->getCollection('Book')->getName())
            ->toEqual($datasource->getCollection('Book')->getName())
            ->and($decoratedDataSource->getCollection('Person')->getName())
            ->toEqual($datasource->getCollection('Person')->getName());
    });

    test('keepCollectionsMatching() should throw an error if a name is unknown', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new PublicationCollectionDatasourceDecorator($datasource);
        $decoratedDataSource->build();


        expect(fn () => $decoratedDataSource->keepCollectionsMatching(['unknown']))
            ->toThrow(ForestException::class, '🌳🌳🌳 Collection unknown not found')
            ->and(fn () => $decoratedDataSource->keepCollectionsMatching([], ['unknown']))
            ->toThrow(ForestException::class, '🌳🌳🌳 Collection unknown not found');
    });

    test('keepCollectionsMatching() should be able to remove "BookPerson" collection', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new PublicationCollectionDatasourceDecorator($datasource);
        $decoratedDataSource->build();

        $decoratedDataSource->keepCollectionsMatching(['Book', 'Person']);


        expect(fn () => $decoratedDataSource->getCollection('BookPerson'))
            ->toThrow(ForestException::class, '🌳🌳🌳 Collection BookPerson was removed')
            ->and($decoratedDataSource->getCollection('Book')->getFields())->not()->toHaveKey('persons')
            ->and($decoratedDataSource->getCollection('Person')->getFields())->not()->toHaveKey('books');
    });

    test('keepCollectionsMatching() should be able to remove "books" collection', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new PublicationCollectionDatasourceDecorator($datasource);
        $decoratedDataSource->build();
        $decoratedDataSource->keepCollectionsMatching([], ['Book']);


        expect(fn () => $decoratedDataSource->getCollection('Book'))
            ->toThrow(ForestException::class, '🌳🌳🌳 Collection Book was removed')
            ->and($decoratedDataSource->getCollection('BookPerson')->getFields())->not()->toHaveKey('book')
            ->and($decoratedDataSource->getCollection('Person')->getFields())->not()->toHaveKey('book');
    });

    test('removeCollection() on a collection that have a polymorphic relation should throw an error', function () {
        $datasource = $this->bucket['datasource'];
        $decoratedDataSource = new PublicationCollectionDatasourceDecorator($datasource);
        $decoratedDataSource->build();

        expect(fn () => $decoratedDataSource->removeCollection('Person'))
            ->toThrow(ForestException::class, "🌳🌳🌳 Cannot remove Person because it's a potential target of polymorphic relation Comment.commentable");
    });

});
