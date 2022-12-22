<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
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
    $collectionProduct = new Collection($datasource, 'Person');
    $collectionProduct->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'  => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL, Operators::IN]),
            'price' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::GREATER_THAN, Operators::LESS_THAN]),
        ]
    );
    $datasource->addCollection($collectionProduct);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'schemaPath'   => sys_get_temp_dir() . '/.forestadmin-schema.json',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
        'agentUrl'     => 'http://localhost/',
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    $caller = new Caller(
        id: 1,
        email: 'sarah.connor@skynet.com',
        firstName: 'sarah',
        lastName: 'connor',
        team: 'survivor',
        renderingId: 1,
        tags: [],
        timezone: 'Europe/Paris',
        permissionLevel: 'admin',
        role: 'dev'
    );

    return [$datasource, $collectionProduct, $caller];
}

test('getSegments() should return the list of segment', function () {
    [$datasource, $collection, $caller] = factorySegmentCollection();

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

test('refineFilter() when there is no filter should return null', function () {
    [$datasource, $collection, $caller] = factorySegmentCollection();

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
});

test('refineFilter() when there is a filter when the segment is not managed by this decorator should return the given filter', function () {
    [$datasource, $collection, $caller] = factorySegmentCollection();
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
});

test('refineFilter() when there is a filter when the segment is managed by this decorator should return the filter with the merged conditionTree', function () {
    [$datasource, $collection, $caller] = factorySegmentCollection();
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
});

test('refineFilter() when there is a filter when the segment is managed by this decorator should throw an error when a conditionTree is not valid', function () {
    [$datasource, $collection, $caller] = factorySegmentCollection();
    $segmentCollection = new SegmentCollection($collection, $datasource);
    $segmentCollection->addSegment(
        'segmentName',
        fn () => new ConditionTreeLeaf(field: 'do not exists', operator: Operators::EQUAL, value: 'aNameValue')
    );

    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf(field: 'name', operator: Operators::EQUAL, value: 'otherNameValue'),
        segment: 'segmentName'
    );

    expect(fn () => $segmentCollection->refineFilter($caller, $filter))->toThrow(ForestException::class, '🌳🌳🌳 Column not found Person.do not exists');
});
