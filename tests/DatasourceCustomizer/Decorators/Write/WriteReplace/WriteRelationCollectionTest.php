<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

function factoryWriteRelationCollection($data = [])
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
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

    $collectionPerson = new Collection($datasource, 'Person');
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

    if (isset($data)) {
        $create = $data['create'];
        $collectionBook = mock($collectionBook)
            ->makePartial()
            ->shouldReceive('create')
            ->andReturn($create['book'])
            ->getMock();

        $collectionPerson = mock($collectionPerson)
            ->makePartial()
            ->shouldReceive('create')
            ->andReturn($create['author'])
            ->getMock();
    }

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionPerson);
    buildAgent($datasource);

    $datasourceDecorator = new WriteDataSourceDecorator($datasource);
    $datasourceDecorator->build();

    /** @var WriteReplaceCollection $newBooks */
    $newBook = $datasourceDecorator->getCollection('Book');
    /** @var WriteReplaceCollection $newBooks */
    $newAuthor = $datasourceDecorator->getCollection('Person');

    return [$newBook, $newAuthor];
}

test('replaceFieldWriting() should create the related record when the relation is not set', function (Caller $caller) {
    $data = [
        'create' => [
            'book'   => [
                'id'       => 1,
                'title'    => 'Memories',
                'authorId' => 1,
            ],
            'author' => [
                'id'        => 1,
                'firstName' => 'John',
                'lastName'  => 'Doe',
            ],
        ],
    ];
    /** @var WriteReplaceCollection $newBook */
    /** @var WriteReplaceCollection $newAuthor */
    [$newBook, $newAuthor] = factoryWriteRelationCollection($data);

    $newBook->replaceFieldWriting('authorFirstName', fn ($value) => ['author' => ['firstName' => $value]]);
    $newBook->replaceFieldWriting('authorLastName', fn ($value) => ['author' => ['lastName' => $value]]);

    expect($newBook->create($caller, [
        'title'           => 'Memories',
        'authorFirstName' => 'John',
        'authorLastName'  => 'Doe',
    ]))->toEqual([
        'id'       => 1,
        'title'    => 'Memories',
        'authorId' => 1,
    ]);
})->with('caller');
