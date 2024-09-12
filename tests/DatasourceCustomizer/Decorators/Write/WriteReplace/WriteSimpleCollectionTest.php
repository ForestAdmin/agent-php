<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\Tests\TestCase;

describe('WriteSimpleCollection', function () {
    $before = static function (TestCase $testCase, $data = []) {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, isReadOnly: true),
                'title' => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::LONGER_THAN, Operators::PRESENT]),
            ]
        );

        if (isset($data['book'])) {
            $collectionBook = \Mockery::mock($collectionBook)
                ->makePartial()
                ->shouldReceive('create')
                ->andReturn($data['book'])
                ->getMock();
        }

        $datasource->addCollection($collectionBook);
        $testCase->buildAgent($datasource);

        $datasourceDecorator = new WriteDataSourceDecorator($datasource);

        $newBook = $datasourceDecorator->getCollection('Book');

        $testCase->bucket['newBook'] = $newBook;
    };

    test('replaceFieldWriting() should work on create', function (Caller $caller) use ($before) {
        $data = [
            'book' => [
                'id'    => 1,
                'title' => 'another value',
            ],
        ];
        $before($this, $data);
        /** @var WriteReplaceCollection $newBook */
        $newBook = $this->bucket['newBook'];

        $newBook->replaceFieldWriting('title', fn ($value) => ['title' => 'another value']);

        expect($newBook->create($caller, [
            'title' => 'my value',
        ]))->toEqual([
            'id'    => 1,
            'title' => 'another value',
        ]);
    })->with('caller');

    test('replaceFieldWriting() should work on update', function (Caller $caller) use ($before) {
        $data = [
            'book' => [
                'id'    => 1,
                'title' => 'another value',
            ],
        ];
        $before($this, $data);
        /** @var WriteReplaceCollection $newBook */
        $newBook = $this->bucket['newBook'];
        $newBook->replaceFieldWriting('title', fn ($value) => ['title' => 'another value']);

        expect($newBook->update(
            $caller,
            new Filter(new ConditionTreeLeaf('id', Operators::EQUAL, 1)),
            ['title' => 'my value']
        ))->toBeNull();
    })->with('caller');

    test('replaceFieldWriting() should throw when the field is unknown', function (Caller $caller) use ($before) {
        $data = [
            'book' => [
                'id'    => 1,
                'title' => 'another value',
            ],
        ];
        $before($this, $data);
        /** @var WriteReplaceCollection $newBook */
        $newBook = $this->bucket['newBook'];
        $newBook->replaceFieldWriting('title', fn ($value) => ['fakeField' => 'another value']);

        expect(fn () => $newBook->create($caller, [
            'title' => 'my value',
        ]))->toThrow(ForestException::class, '🌳🌳🌳 Unknown field : fakeField');
    })->with('caller');

    test('update() with a key that contains a null value should not remove the field of the patch', function (Caller $caller) use ($before) {
        $patch = [
            'id'    => 1,
            'title' => null,
        ];

        $before($this, []);
        $newBook = $this->bucket['newBook'];

        \Mockery::mock($newBook->getDatasource()->getCollection('Book'))
            ->shouldReceive('update')
            ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), $patch)
            ->andReturnNull()
            ->getMock();


        expect($newBook->update(
            $caller,
            new Filter(new ConditionTreeLeaf('id', Operators::EQUAL, 1)),
            $patch
        ))->toBeNull();

    })->with('caller');
});
