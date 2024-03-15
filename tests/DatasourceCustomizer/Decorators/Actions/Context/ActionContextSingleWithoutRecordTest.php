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

describe('action context single without records', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'title' => new ColumnSchema(columnType: PrimitiveType::STRING),
            ]
        );

        $collectionBook = \Mockery::mock($collectionBook)
            ->shouldReceive('list')
            ->andReturn([]);

        $datasource->addCollection($collectionBook->getMock());
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);
        $datasourceDecorator->build();

        /** @var ActionCollection $computedCollection */
        $this->bucket['actionCollection'] = $datasourceDecorator->getCollection('Book');
        $this->bucket['bookCollection'] = $collectionBook;
    });

    test('getRecord should return empty field if no there is no record', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContextSingle($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getRecord(['id', 'title']))->toEqual([]);
    })->with('caller');


    test('getRecordId should return null if there is no the corresponding id', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContextSingle($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getRecordId())->toBeNull();
    })->with('caller');

    test('getCompositeRecordId should return an empty array when there is no record', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContextSingle($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getCompositeRecordId())->toEqual([]);
    })->with('caller');

});
