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

function factoryActionContext()
{
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

//    if ($aggregation) {
//        $collectionBook->shouldReceive('aggregate')
//            ->andReturn($aggregation->apply($records, 'Europe/Paris'));
//    }

    $datasource->addCollection($collectionBook->getMock());
    buildAgent($datasource);

    $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);
    $datasourceDecorator->build();

    /** @var ActionCollection $computedCollection */
    $actionCollection = $datasourceDecorator->getCollection('Book');

    return $actionCollection;
}

test('getFormValue should return the correct value when a key is defined', closure: function (Caller $caller) {
    $actionCollection = factoryActionContext();
    $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

    expect($context->getFormValue('title'))->toEqual('Foundation');
})->with('caller');

test('getFormValue should return null value when a key doesn\'t exist', closure: function (Caller $caller) {
    $actionCollection = factoryActionContext();
    $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

    expect($context->getFormValue('foo'))->toBeNull();
})->with('caller');

test('getRecords should return the correct values of the list collection', closure: function (Caller $caller) {
    $actionCollection = factoryActionContext();
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

test('getRecordIds ', closure: function (Caller $caller) {
    $actionCollection = factoryActionContext();
    $context = new ActionContext($actionCollection, $caller, new PaginatedFilter(), ['title' => 'Foundation']);

    expect($context->getRecordIds())->toEqual([1, 2]);
})->with('caller');
