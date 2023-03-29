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

function factoryWriteRelationCollection($data = [])
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, isReadOnly: true),
            'title' => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::LONGER_THAN, Operators::PRESENT]),
        ]
    );

    if (isset($data)) {
        $create = $data['create'];
        $collectionBook = mock($collectionBook)
            ->makePartial()
            ->shouldReceive('create')
            ->andReturn($create['book'])
            ->getMock();
    }

    $datasource->addCollection($collectionBook);
    buildAgent($datasource);

    $datasourceDecorator = new WriteDataSourceDecorator($datasource);
    $datasourceDecorator->build();

    /** @var WriteReplaceCollection $newBooks */
    $newBook = $datasourceDecorator->getCollection('Book');

    return $newBook;
}

test('replaceFieldWriting() should work', function (Caller $caller) {
    $data = [
        'create' => [
            'book' => [
                'id'    => 1,
                'title' => 'another value',
            ],
        ],
    ];
    /** @var WriteReplaceCollection $newAuthor */
    $newBook = factoryWriteRelationCollection($data);

    $newBook->replaceFieldWriting('title', fn ($value) => ['title' => 'another value']);

    expect($newBook->create($caller, [
        'title' => 'my value',
    ]))->toEqual([
        'id'    => 1,
        'title' => 'another value',
    ]);
})->with('caller');
