<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart\ChartCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart\CollectionChartContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

describe('CollectionChart', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id1'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
                'id2'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            ]
        );

        $records = [
            [
                'id1'    => 1,
                'id2'    => 2,
            ],
        ];
        $collectionBook = \Mockery::mock($collectionBook)
            ->shouldReceive('list')
            ->andReturn($records);

        $datasource->addCollection($collectionBook->getMock());
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ChartCollection::class);
        $datasourceDecorator->build();

        $this->bucket['bookCollection'] = $datasourceDecorator->getCollection('Book');
    });

    test('recordId should throw an error', function (Caller $caller) {
        $chartCollection = $this->bucket['bookCollection'];
        $context = new CollectionChartContext($chartCollection, $caller, [1, 2]);

        expect(static fn () => $context->getRecordId())->toThrow(
            ForestException::class,
            "🌳🌳🌳 Collection is using a composite pk: use 'context->getCompositeRecordId()"
        );
    })->with('caller');

    test('recordId should work', function (Caller $caller) {
        $chartCollection = $this->bucket['bookCollection'];
        $context = new CollectionChartContext($chartCollection, $caller, [1]);

        expect($context->getRecordId())->toEqual(1);
    })->with('caller');

    test('compositeRecordId should return the recordId', function (Caller $caller) {
        $chartCollection = $this->bucket['bookCollection'];
        $context = new CollectionChartContext($chartCollection, $caller, [1, 2]);

        expect($context->getCompositeRecordId())->toEqual([1, 2]);
    })->with('caller');

    test('getRecord should return the record', function (Caller $caller) {
        $chartCollection = $this->bucket['bookCollection'];
        $context = new CollectionChartContext($chartCollection, $caller, [1, 2]);

        expect($context->getRecord(['id1', 'id2']))->toEqual(['id1' => 1, 'id2' => 2]);
    })->with('caller');
});
