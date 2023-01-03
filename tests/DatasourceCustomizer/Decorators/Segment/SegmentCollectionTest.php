<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Segment\SegmentCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factorySegmentCollection()
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
    $datasource->addCollection($collectionProduct);

    buildAgent($datasource);

    return [$datasource, $collectionProduct];
}

test('getSegments() should return the list of segment', function () {
    [$datasource, $collection] = factorySegmentCollection();

    $segmentCollection = new SegmentCollection($collection, $datasource);
    $segmentCollection->addSegment(
        'segmentName',
        fn () => [
            'field'    => 'price',
            'operator' => Operators::GREATER_THAN,
            'value'    => 750,
        ]
    );
    $segmentCollection->addSegment(
        'segmentName2',
        fn () => [
            'field'    => 'price',
            'operator' => Operators::LESS_THAN,
            'value'    => 1000,
        ]
    );

    expect($segmentCollection->getSegments())->toArray()->toEqual(['segmentName', 'segmentName2']);
});

test('refineFilter() when there is no filter should return null', function (Caller $caller) {
    [$datasource, $collection] = factorySegmentCollection();

    $segmentCollection = new SegmentCollection($collection, $datasource);
    $segmentCollection->addSegment(
        'segmentName',
        fn () => [
            'field'    => 'price',
            'operator' => Operators::GREATER_THAN,
            'value'    => 750,
        ]
    );

    expect($segmentCollection->refineFilter($caller, null))->toBeNull();
})->with('caller');

test('refineFilter() when there is a filter when the segment is not managed by this decorator should return the given filter', function (Caller $caller) {
    [$datasource, $collection] = factorySegmentCollection();
    $segmentCollection = new SegmentCollection($collection, $datasource);
    $segmentCollection->addSegment(
        'segmentName',
        fn () => [
            'field'    => 'price',
            'operator' => Operators::GREATER_THAN,
            'value'    => 750,
        ]
    );
    $filter = new Filter(segment: 'aSegment');

    expect($segmentCollection->refineFilter($caller, $filter))->toEqual($filter);
})->with('caller');

test('refineFilter() when there is a filter when the segment is managed by this decorator should return the filter with the merged conditionTree', function (Caller $caller) {
    [$datasource, $collection] = factorySegmentCollection();
    $segmentCollection = new SegmentCollection($collection, $datasource);
    $segmentCollection->addSegment(
        'segmentName',
        fn () => new ConditionTreeLeaf(field: 'name', operator: Operators::EQUAL, value: 'aNameValue')
    );

    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf(field: 'name', operator: Operators::EQUAL, value: 'otherNameValue'),
        segment: 'segmentName'
    );

    expect($segmentCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeBranch(
                'And',
                [
                    new ConditionTreeLeaf(field: 'name', operator: Operators::EQUAL, value: 'otherNameValue'),
                    new ConditionTreeLeaf(field: 'name', operator: Operators::EQUAL, value: 'aNameValue'),
                ]
            ),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when there is a filter when the segment is managed by this decorator should throw an error when a conditionTree is not valid', function (Caller $caller) {
    [$datasource, $collection] = factorySegmentCollection();
    $segmentCollection = new SegmentCollection($collection, $datasource);
    $segmentCollection->addSegment(
        'segmentName',
        fn () => new ConditionTreeLeaf(field: 'do not exists', operator: Operators::EQUAL, value: 'aNameValue')
    );

    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf(field: 'name', operator: Operators::EQUAL, value: 'otherNameValue'),
        segment: 'segmentName'
    );

    expect(fn () => $segmentCollection->refineFilter($caller, $filter))->toThrow(ForestException::class, '🌳🌳🌳 Column not found Product.do not exists');
})->with('caller');
