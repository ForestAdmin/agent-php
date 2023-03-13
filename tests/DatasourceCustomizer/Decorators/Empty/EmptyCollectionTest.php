<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Empty\EmptyCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factoryEmptyCollection($args = [])
{
    $datasource = new Datasource();
    $collectionProduct = new Collection($datasource, 'Product');
    $collectionProduct->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'  => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL, Operators::IN]),
            'price' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::GREATER_THAN, Operators::LESS_THAN]),
        ]
    );

    if (isset($args['listing'])) {
        $collectionProduct = mock($collectionProduct)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type(Projection::class))
            ->andReturn($args['listing'])
            ->getMock();
    }

    if (isset($args['update'])) {
        $collectionProduct = mock($collectionProduct)
            ->shouldReceive('update')
            ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type([]))
            ->andReturnNull()
            ->getMock();
    }

    if (isset($args['delete'])) {
        $collectionProduct = mock($collectionProduct)
            ->shouldReceive('delete')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class))
            ->andReturnNull()
            ->getMock();
    }

    if (isset($args['aggregate'])) {
        $collectionProduct = mock($collectionProduct)
            ->shouldReceive('aggregate')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null, null)
            ->andReturn($args['aggregate'])
            ->getMock();
    }


    $datasource->addCollection($collectionProduct);

    $datasourceDecorator = new DatasourceDecorator($datasource, EmptyCollection::class);
    $datasourceDecorator->build();

    buildAgent($datasource);

    return [$datasourceDecorator, $collectionProduct];
}

test('schema should not be changed', function () {
    [$datasourceDecorator, $collection] = factoryEmptyCollection();

    expect($datasourceDecorator->getCollection('Product')->getFields())->toEqual($collection->getFields());
});

test('valid queries - list() should be called with overlapping Ins', closure: function (Caller $caller) {
    $data = [['id' => 2]];
    [$datasourceDecorator, $collection] = factoryEmptyCollection(['listing' => $data]);

    /** @var EmptyCollection $newCollectionProduct */
    $newCollectionProduct = $datasourceDecorator->getCollection('Product');
    $records = $newCollectionProduct->list(
        $caller,
        new PaginatedFilter(
            conditionTree: new ConditionTreeBranch(
                aggregator: 'And',
                conditions: [
                    new ConditionTreeLeaf(field: 'id', operator: 'In', value: [1,2]),
                    new ConditionTreeLeaf(field: 'id', operator: 'In', value: [2,3]),
                ]
            )
        ),
        new Projection()
    );

    expect($records)->toEqual($data);
})->with('caller');

test('valid queries - list() should be called with empty And', closure: function (Caller $caller) {
    $data = [['id' => 2]];
    [$datasourceDecorator, $collection] = factoryEmptyCollection(['listing' => $data]);

    /** @var EmptyCollection $newCollectionProduct */
    $newCollectionProduct = $datasourceDecorator->getCollection('Product');
    $records = $newCollectionProduct->list(
        $caller,
        new PaginatedFilter(
            conditionTree: new ConditionTreeBranch(
                aggregator: 'And',
                conditions: []
            )
        ),
        new Projection()
    );

    expect($records)->toEqual($data);
})->with('caller');

test('valid queries - list() should be called with only non Equal/In leafs', closure: function (Caller $caller) {
    $data = [['id' => 2]];
    [$datasourceDecorator, $collection] = factoryEmptyCollection(['listing' => $data]);

    /** @var EmptyCollection $newCollectionProduct */
    $newCollectionProduct = $datasourceDecorator->getCollection('Product');
    $records = $newCollectionProduct->list(
        $caller,
        new PaginatedFilter(
            conditionTree: new ConditionTreeBranch(
                aggregator: 'And',
                conditions: [
                    new ConditionTreeLeaf(field: 'id', operator: 'Today'),
                ]
            )
        ),
        new Projection()
    );

    expect($records)->toEqual($data);
})->with('caller');

test('valid queries - update() should be called with overlapping Order incompatible equals', closure: function (Caller $caller) {
    [$datasourceDecorator, $collection] = factoryEmptyCollection(['update' => true]);

    /** @var EmptyCollection $newCollectionProduct */
    $newCollectionProduct = $datasourceDecorator->getCollection('Product');
    $records = $newCollectionProduct->update(
        $caller,
        new PaginatedFilter(
            conditionTree: new ConditionTreeBranch(
                aggregator: 'And',
                conditions: [
                    new ConditionTreeLeaf(field: 'id', operator: 'Equal', value: 4),
                    new ConditionTreeLeaf(field: 'id', operator: 'Equal', value: 5),
                ]
            )
        ),
        ['title' => 'new Title']
    );

    expect($records)->toBeNull();
})->with('caller');

test('valid queries - delete() should be called with null condition Tree', closure: function (Caller $caller) {
    [$datasourceDecorator, $collection] = factoryEmptyCollection(['delete' => true]);

    /** @var EmptyCollection $newCollectionProduct */
    $newCollectionProduct = $datasourceDecorator->getCollection('Product');
    $records = $newCollectionProduct->delete(
        $caller,
        new Filter(),
    );

    expect($records)->toBeNull();
})->with('caller');

test('valid queries - aggregate() should be called with simple query', closure: function (Caller $caller) {
    $data = [['value' => 2, 'group' => []]];
    [$datasourceDecorator, $collection] = factoryEmptyCollection(['aggregate' => $data]);

    /** @var EmptyCollection $newCollectionProduct */
    $newCollectionProduct = $datasourceDecorator->getCollection('Product');
    $records = $newCollectionProduct->aggregate(
        $caller,
        new Filter(
            conditionTree: new ConditionTreeLeaf(field: 'id', operator: 'Equal', value: null)
        ),
        new Aggregation('Count')
    );

    expect($records)->toEqual($data);
})->with('caller');
