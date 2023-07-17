<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\Tests\CollectionMocked;

\Ozzie\Nest\describe('WriteManyToOneCollection', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new CollectionMocked($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'              => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true, isReadOnly: true),
                'authorId'        => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
                'author'          => new ManyToOneSchema(
                    foreignKey: 'authorId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
                'title'           => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::LONGER_THAN, Operators::PRESENT]),
            ]
        );

        $collectionPerson = new CollectionMocked($datasource, 'Person');
        $collectionPerson->addFields(
            [
                'id'             => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
                'firstName'      => new ColumnSchema(columnType: PrimitiveType::STRING),
                'lastName'       => new ColumnSchema(columnType: PrimitiveType::STRING),
            ]
        );

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionPerson);
        $this->buildAgent($datasource);

        $datasourceDecorator = new WriteDataSourceDecorator($datasource);
        $datasourceDecorator->build();

        $newBook = $datasourceDecorator->getCollection('Book');
        $newAuthor = $datasourceDecorator->getCollection('Person');

        $this->bucket = [$newBook, $collectionBook, $collectionPerson];
    });

    test('should create the related record when it does not exists', function (Caller $caller) {
        /** @var WriteReplaceCollection $newBook */
        /** @var WriteReplaceCollection $newAuthor */
        [$newBook, $collectionBook, $collectionPerson] = $this->bucket;

        $recordsAuthor = [
            [
                'id'        => 1,
                'firstName' => 'Isaac',
                'lastName'  => 'Asimov',
            ],
            [
                'id'        => 2,
                'firstName' => 'Roberto',
                'lastName'  => 'Saviano',
            ],
        ];

        $recordsBook = [
            [
                'id'     => 1,
                'author' => $recordsAuthor[0],
                'title'  => 'Foundation',
            ],
            [
                'id'     => 2,
                'author' => $recordsAuthor[1],
                'title'  => 'Gomorrah',
            ],
            [
                'id'     => 3,
                'title'  => 'Harry Potter',
            ],
        ];

        $collectionBook->listReturn = $recordsBook;
        $collectionPerson->createReturn = [
            'id'        => 1,
            'firstName' => 'Isaac',
            'lastName'  => 'Asimov',
        ];

        $newBook->update(
            $caller,
            new Filter(new ConditionTreeLeaf('id', Operators::EQUAL, 3)),
            ['title' => 'new title', 'author' => ['firstName' => 'John', 'lastName' => 'Doe']]
        );

        expect($collectionPerson->paramsUpdate['patch'])->toEqual(['firstName' => 'John', 'lastName' => 'Doe'])
            ->and($collectionBook->paramsUpdate['patch'])->toEqual(['authorId' => 1]);
    })->with('caller');

    test('should update the related record when it exists', function (Caller $caller) {
        /** @var WriteReplaceCollection $newBook */
        /** @var WriteReplaceCollection $newAuthor */
        [$newBook, $collectionBook, $collectionPerson] = $this->bucket;

        $recordsAuthor = [
            [
                'id'        => 1,
                'firstName' => 'Isaac',
                'lastName'  => 'Asimov',
            ],
        ];

        $recordsBook = [
            [
                'id'     => 1,
                'author' => $recordsAuthor[0],
                'title'  => 'Foundation',
            ],
        ];
        $collectionBook->listReturn = $recordsBook;

        $newBook->update(
            $caller,
            new Filter(new ConditionTreeLeaf('id', Operators::EQUAL, 1)),
            ['title' => 'new title', 'author' => ['id' => 1, 'firstName' => 'New name']]
        );

        expect($collectionPerson->paramsUpdate['patch'])->toEqual(['firstName' => 'New name','id' => 1])
            ->and($collectionBook->paramsUpdate['patch'])->toEqual(['title' => 'new title']);
    })->with('caller');

});
