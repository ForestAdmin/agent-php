<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContextSingle;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

describe('action context single', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'title' => new ColumnSchema(columnType: PrimitiveType::STRING),
            ]
        );

        $records = [
            [
                'id'    => 1,
                'title' => 'Foundation',
            ],
            [
                'id'    => 2,
                'title' => 'Beat the dealer',
            ],
        ];
        $collectionBook = \Mockery::mock($collectionBook)
            ->shouldReceive('list')
            ->andReturn($records);

        $datasource->addCollection($collectionBook->getMock());
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);

        /** @var ActionCollection $computedCollection */
        $this->bucket['actionCollection'] = $datasourceDecorator->getCollection('Book');
        $this->bucket['bookCollection'] = $collectionBook;
    });

    test('getRecord should return the correct value', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContextSingle($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getRecord(['id', 'title']))->toEqual(
            [
                'id'    => 1,
                'title' => 'Foundation',
            ]
        );
    })->with('caller');

    test('getRecordId should return the corresponding id', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContextSingle($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getRecordId())->toEqual(1);
    })->with('caller');

    test('getCompositeRecordId should return the ids of records', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContextSingle($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getCompositeRecordId())->toEqual([1]);
    })->with('caller');
});
