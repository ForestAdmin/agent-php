<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\Tests\CollectionMocked;

\Ozzie\Nest\describe('WriteManyToOneCollection', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new CollectionMocked($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'              => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, isReadOnly: true),
                'authorId'        => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
                'author'          => new ManyToOneSchema(
                    foreignKey: 'authorId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
                'title'           => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::LONGER_THAN, Operators::PRESENT]),
                // Those fields will have rewrite handler to the corresponding author fields
                'authorFirstName' => new ColumnSchema(columnType: PrimitiveType::STRING),
                'authorLastName'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            ]
        );

        $collectionPerson = new CollectionMocked($datasource, 'Person');
        $collectionPerson->addFields(
            [
                'id'             => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'firstName'      => new ColumnSchema(columnType: PrimitiveType::STRING),
                'lastName'       => new ColumnSchema(columnType: PrimitiveType::STRING),
                'book'           => new OneToOneSchema(
                    originKey: 'authorId',
                    originKeyTarget: 'id',
                    foreignCollection: 'Book',
                ),
                // This field will have a rewrite rule to alias firstName
                'firstNameAlias' => new ColumnSchema(columnType: PrimitiveType::STRING),
            ]
        );

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionPerson);
        $this->buildAgent($datasource);

        $datasourceDecorator = new WriteDataSourceDecorator($datasource);
        $datasourceDecorator->build();

        $newBook = $datasourceDecorator->getCollection('Book');
        $newAuthor = $datasourceDecorator->getCollection('Person');

        $this->bucket = [$newBook, $newAuthor, $collectionBook, $collectionPerson];
    });


    test('replaceFieldWriting() should create the related record when the relation is not set', function (Caller $caller) {
        /** @var WriteReplaceCollection $newBook */
        /** @var WriteReplaceCollection $newAuthor */
        [$newBook, $newAuthor, $collectionBook, $collectionPerson] = $this->bucket;

        $collectionBook->createReturn = [
            'id'        => 1,
            'title'     => 'Memories',
            'authorId'  => 1,
        ];

        $collectionPerson->createReturn = [
            'id'        => 1,
            'firstName' => 'Isaac',
            'lastName'  => 'Asimov',
        ];

        $newBook->replaceFieldWriting('authorFirstName', fn ($value) => ['author' => ['firstName' => $value]]);
        $newBook->replaceFieldWriting('authorLastName', fn ($value) => ['author' => ['lastName' => $value]]);

        $newBook->create($caller, [
            'title'           => 'Memories',
            'authorFirstName' => 'John',
            'authorLastName'  => 'Doe',
        ]);

        expect($collectionPerson->createReturn)->toEqual(['id' => 1, 'firstName' => 'Isaac', 'lastName'  => 'Asimov',])
            ->and($collectionBook->createReturn)->toEqual(['id' => 1, 'title' => 'Memories', 'authorId' => 1]);
    })->with('caller');

    test('replaceFieldWriting() should associate the related record when the relation is set', function (Caller $caller) {
        /** @var WriteReplaceCollection $newBook */
        /** @var WriteReplaceCollection $newAuthor */
        [$newBook, $newAuthor, $collectionBook, $collectionPerson] = $this->bucket;

        $collectionBook->createReturn = [
            'id'        => 1,
            'title'     => 'Memories',
            'authorId'  => 1,
        ];

        $newBook->replaceFieldWriting('authorFirstName', fn ($value) => ['author' => ['firstName' => $value]]);
        $newBook->replaceFieldWriting('authorLastName', fn ($value) => ['author' => ['lastName' => $value]]);

        $newBook->create($caller, [
            'title'           => 'Memories',
            'authorId'        => 1,
            'authorFirstName' => 'John',
            'authorLastName'  => 'Doe',
        ]);

        expect($collectionPerson->paramsUpdate['patch'])->toEqual(['firstName' => 'John', 'lastName' => 'Doe'])
            ->and($collectionBook->createReturn)->toEqual(['id' => 1, 'title' => 'Memories', 'authorId' => 1]);
    })->with('caller');

    test('replaceFieldWriting() should thrown when a field received several values', function (Caller $caller) {
        /** @var WriteReplaceCollection $newBook */
        /** @var WriteReplaceCollection $newAuthor */
        [$newBook, $newAuthor, $collectionBook, $collectionPerson] = $this->bucket;

        $newBook->replaceFieldWriting('authorFirstName', fn ($value) => ['author' => ['firstName' => $value]]);
        $newBook->replaceFieldWriting('authorLastName', fn ($value) => ['author' => ['firstName' => $value, 'lastName' => $value]]);

        expect(fn () => $newBook->create($caller, [
            'title'           => 'Memories',
            'authorId'        => 1,
            'authorFirstName' => 'John',
            'authorLastName'  => 'Doe',
        ]))->toThrow(ForestException::class, '🌳🌳🌳 Conflict value on the field firstName. It received several values.');
    })->with('caller');

    test('replaceFieldWriting() should thrown when the field doesn\'t exist into the collection', function (Caller $caller) {
        /** @var WriteReplaceCollection $newBook */
        /** @var WriteReplaceCollection $newAuthor */
        [$newBook, $newAuthor, $collectionBook, $collectionPerson] = $this->bucket;

        expect(fn () => $newBook->create($caller, [
            'FIELD-DONT-EXIST' => 'Memories',
            'authorId'         => 1,
            'authorFirstName'  => 'John',
            'authorLastName'   => 'Doe',
        ]))->toThrow(ForestException::class, '🌳🌳🌳 Unknown field : FIELD-DONT-EXIST');
    })->with('caller');

});
