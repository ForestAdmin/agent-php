<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

\Ozzie\Nest\describe('Action context', function () {
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
        $collectionBook = mock($collectionBook)
            ->shouldReceive('list')
            ->andReturn($records);

        $datasource->addCollection($collectionBook->getMock());
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);
        $datasourceDecorator->build();

        /** @var ActionCollection $computedCollection */
        $this->bucket['actionCollection'] = $datasourceDecorator->getCollection('Book');
    });

    test('getFormValue should return the correct value when a key is defined', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getFormValue('title'))->toEqual('Foundation');
    })->with('caller');

    test('getFormValue should return null value when a key doesn\'t exist', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getFormValue('foo'))->toBeNull();
    })->with('caller');

    test('getRecords should return the correct values of the list collection', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getRecords(['id', 'title']))->toEqual(
            [
                [
                    'id'    => 1,
                    'title' => 'Foundation',
                ],
                [
                    'id'    => 2,
                    'title' => 'Beat the dealer',
                ],
            ]
        );
    })->with('caller');

    test('getRecordIds should return the pk list', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getRecordIds())->toEqual([1, 2]);
    })->with('caller');

    test('getFormValues should return all form values', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getFormValues())->toEqual(['title' => 'Foundation']);
    })->with('caller');

    test('getFilter should return the context filter', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getFilter())->toEqual(new PaginatedFilter());
    })->with('caller');

    test('getChangeField should return the changeField attribute when it set', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation'], [], 'MyChangeField');

        expect($context->getChangeField())->toEqual('MyChangeField');
    })->with('caller');

    test('getChangeField should return null when changeField is not set', closure: function (Caller $caller) {
        $actionCollection = $this->bucket['actionCollection'];
        $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

        expect($context->getChangeField())->toBeNull();
    })->with('caller');
});
