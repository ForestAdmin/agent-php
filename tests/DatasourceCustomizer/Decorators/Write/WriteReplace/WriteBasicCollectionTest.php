<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

describe('WriteBasicCollection', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, isReadOnly: true),
                'title' => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::PRESENT], isReadOnly: true),
            ]
        );

        $datasource->addCollection($collectionBook);
        $this->buildAgent($datasource);

        $datasourceDecorator = new WriteDataSourceDecorator($datasource);

        $newBooks = $datasourceDecorator->getCollection('Book');

        $this->bucket['newBooks'] = $newBooks;
    });

    test('replaceFieldWriting() should throw when rewriting an inexistant field', function () {
        $newBooks = $this->bucket['newBooks'];

        expect(fn () => $newBooks->replaceFieldWriting('__dontExist', fn () => ''))
            ->toThrow(ForestException::class, '🌳🌳🌳 The given field "__dontExist" does not exist on the Book collection.');
    });

    test('should mark fields as writable when handler is defined', function () {
        $newBooks = $this->bucket['newBooks'];

        expect($newBooks->getFields()['title']->isReadOnly())->toBeTrue();

        $newBooks->replaceFieldWriting('title', fn () => '');
        expect($newBooks->getFields()['title']->isReadOnly())->toBeFalse();
    });

    test('should mark fields as readonly when handler is null', function () {
        $newBooks = $this->bucket['newBooks'];

        expect($newBooks->getFields()['title']->isReadOnly())->toBeTrue();

        $newBooks->replaceFieldWriting('title', null);
        expect($newBooks->getFields()['title']->isReadOnly())->toBeTrue();
    });

});
