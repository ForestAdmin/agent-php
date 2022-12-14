<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Search\SearchCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factorySearchCollection()
{
    $datasource = new Datasource();
    $collectionPerson = new Collection($datasource, 'Person');
    $datasource->addCollection($collectionPerson);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
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

    return [$datasource, $collectionPerson, $caller];
}

test('replaceSearch() should work', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);
    $replace = fn ($search) => [
        'aggregator' => 'And',
        'conditions' => [
            ['field' => 'id', 'operator' => Operators::EQUAL, 'value' => 1],
        ],
    ];
    $searchCollection->replaceSearch($replace);

    expect(invokeProperty($searchCollection, 'replacer'))->toEqual($replace);
});

test('isSearchable() should return true', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);

    expect($searchCollection->isSearchable())->toBeTrue();
});

test('refineFilter() when the search value is null should return the given filter to return all records', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: null);

    expect($searchCollection->refineFilter($caller, $filter))->toEqual($filter);
});

test('refineFilter() when the collection schema is searchable should return the given filter without adding condition', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(true);
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'a text');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual($filter);
});

test('refineFilter() when a replacer is provided it should be used instead of the default one', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'something');
    $replace = fn ($value) => [
        'field' => 'id', 'operator' => Operators::EQUAL, 'value' => $value,
    ];
    $searchCollection->replaceSearch($replace);

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'id', 'operator' => Operators::EQUAL, 'value' => 'something']),
            search: null,
            segment: null
        ),
    );
});

test('refineFilter() when the search is defined and the collection schema is not searchable and when the search is empty returns the same filter and set search as null', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(false);
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '     ', searchExtended: false);

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: null,
            search: null,
            segment: null
        ),
    );
});

test('refineFilter() when the search is defined and the collection schema is not searchable and when the filter contains already conditions should add its conditions to the filter', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::ICONTAINS]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(
        conditionTree: new ConditionTreeBranch(
            'And',
            [new ConditionTreeLeaf(field: 'label', operator: Operators::EQUAL, value: 'value')]
        ),
        search: 'a text',
    );

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeBranch(
                'And',
                [
                    new ConditionTreeLeaf(field: 'label', operator: Operators::EQUAL, value: 'value'),
                    new ConditionTreeLeaf(field: 'label', operator: Operators::ICONTAINS, value: 'a text'),
                ]
            ),
            search: null,
            searchExtended: false,
            segment: null
        ),
    );
});

test('refineFilter() when the search is defined and the collection schema is not searchable when the search is a string and the column type is a string should return filter with "contains" condition and "or" aggregator', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::ICONTAINS, Operators::CONTAINS]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'a text');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'label', 'operator' => Operators::CONTAINS, 'value' => 'a text']),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
});

test('refineFilter() when the search is defined and the collection schema is not searchable when searching on a string that only supports Equal should return filter with "equal" condition', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'a text');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'label', 'operator' => Operators::EQUAL, 'value' => 'a text']),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
});

test('refineFilter() search is a case insensitive string and both operators are supported should return filter with "contains" condition and "or" aggregator', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::ICONTAINS, Operators::CONTAINS]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '@#*$(@#*$(23423423');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'label', 'operator' => Operators::CONTAINS, 'value' => '@#*$(@#*$(23423423']),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
});

test('refineFilter() when the search is an uuid and the column type is an uuid should return filter with "equal" condition and "or" aggregator', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('number', new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '2d162303-78bf-599e-b197-93590ac3d315');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'number', 'operator' => Operators::EQUAL, 'value' => '2d162303-78bf-599e-b197-93590ac3d315']),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
});

test('refineFilter() when the search is a number and the column type is a number returns "equal" condition, "or" aggregator and cast value to Number', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::ICONTAINS]));
    $collection->setField('number', new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '1584');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeBranch(
                'Or',
                [
                    new ConditionTreeLeaf(field: 'label', operator: Operators::ICONTAINS, value: '1584'),
                    new ConditionTreeLeaf(field: 'number', operator: Operators::EQUAL, value: 1584),
                ]
            ),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
});

test('refineFilter() when the search is an string and the column type is an enum should return filter with "equal" condition and "or" aggregator', function () {
    [$datasource, $collection, $caller] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::ENUM, filterOperators: [Operators::EQUAL], enumValues: ['AnEnUmVaLue']));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'anenumvalue');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeLeaf(field: 'label', operator: Operators::EQUAL, value: 'AnEnUmVaLue'),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
});

