<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factoryWriteBasicCollection()
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, isReadOnly: true),
            'title' => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::PRESENT], isReadOnly: true),
        ]
    );

    $datasource->addCollection($collectionBook);
    buildAgent($datasource);

    $datasourceDecorator = new WriteDataSourceDecorator($datasource);
    $datasourceDecorator->build();

    /** @var WriteReplaceCollection $newBooks */
    $newBooks = $datasourceDecorator->getCollection('Book');

    return $newBooks;
}

test('replaceFieldWriting() should throw when rewriting an inexistant field', function () {
    $newBooks = factoryWriteBasicCollection();

    expect(fn () => $newBooks->replaceFieldWriting('__dontExist', fn () => ''))
        ->toThrow(ForestException::class, '🌳🌳🌳 The given field "__dontExist" does not exist on the Book collection.');
});

test('should mark fields as writable when handler is defined', function () {
    $newBooks = factoryWriteBasicCollection();

    expect($newBooks->getFields()['title']->isReadOnly())->toBeTrue();

    $newBooks->replaceFieldWriting('title', fn () => '');
    expect($newBooks->getFields()['title']->isReadOnly())->toBeFalse();
});

test('should mark fields as readonly when handler is null', function () {
    $newBooks = factoryWriteBasicCollection();

    expect($newBooks->getFields()['title']->isReadOnly())->toBeTrue();

    $newBooks->replaceFieldWriting('title', null);
    expect($newBooks->getFields()['title']->isReadOnly())->toBeTrue();
});
