<?php

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BaseTransformer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection\RenameCollectionDatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

\Ozzie\Nest\describe('RenameCollectionDecoratorCollection', function () {
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
                'persons'   => new ManyToManySchema(
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
                'person'   => new ManyToOneSchema(
                    foreignKey: 'personId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
                'book'   => new ManyToOneSchema(
                    foreignKey: 'bookId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Book',
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
                'books' => new ManyToManySchema(
                    originKey: 'personId',
                    originKeyTarget: 'id',
                    foreignKey: 'bookId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Book',
                    throughCollection: 'BookPerson',
                ),
            ]
        );

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionBookPerson);
        $datasource->addCollection($collectionPerson);
        $this->buildAgent($datasource);

        $decoratedDataSource = new RenameCollectionDatasourceDecorator($datasource);
        $decoratedDataSource->build();
        $this->bucket = compact('decoratedDataSource');

    });

    test('getName() should return the default collection name when there is no rename action', function () {
        $decoratedDataSource = $this->bucket['decoratedDataSource'];

        expect($decoratedDataSource->getCollection('Person')->getName())->toEqual('Person');
    });

    test('getName() should return the new name when there is a rename action', function () {
        $decoratedDataSource = $this->bucket['decoratedDataSource'];
        $decoratedDataSource->renameCollections(['Person' => 'User']);

        expect($decoratedDataSource->getCollection('User')->getName())->toEqual('User');
    });

    test('getFields() should return the fields of the collection', function () {
        $decoratedDataSource = $this->bucket['decoratedDataSource'];
        $decoratedDataSource->renameCollections(['Person' => 'User']);

        expect($decoratedDataSource->getCollection('User')->getFields())->toHaveKeys(['id', 'book']);
    });

    test('makeTransformer() should return ', function () {
        $decoratedDataSource = $this->bucket['decoratedDataSource'];
        $decoratedDataSource->renameCollections(['Person' => 'User']);

        expect($decoratedDataSource->getCollection('User')->makeTransformer())->toBeInstanceOf(BaseTransformer::class);
    });
});
