<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Utils\ConditionTreeParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factoryConditionTreeParser()
{
    $datasource = new Datasource();
    $collectionCategory = new Collection($datasource, 'Category');
    $collectionCategory->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::GREATER_THAN, Operators::LESS_THAN], isPrimaryKey: true),
            'label'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'active' => new ColumnSchema(columnType: PrimitiveType::BOOLEAN, filterOperators: [Operators::IN]),
        ]
    );

    $datasource->addCollection($collectionCategory);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    return $collectionCategory;
}

test('ConditionTreeParser() should failed if provided something else', function () {
    $collectionCategory = factoryConditionTreeParser();

    expect(fn () => ConditionTreeParser::fromPLainObject($collectionCategory, []))
        ->toThrow(\Exception::class, 'Failed to instantiate condition tree');
});

test('ConditionTreeParser() should work with aggregator', function () {
    $collectionCategory = factoryConditionTreeParser();

    $filters = [
        'aggregator' => 'and',
        'conditions' => [
            ['field' => 'id', 'operator' => 'Less_Than', 'value' => 'something'],
            ['field' => 'id', 'operator' => 'Greater_Than', 'value' => 'something'],
        ],
    ];

    expect(ConditionTreeParser::fromPLainObject($collectionCategory, $filters))
        ->toEqual(new ConditionTreeBranch(
            ucfirst($filters['aggregator']),
            [
                new ConditionTreeLeaf(...$filters['conditions'][0]),
                new ConditionTreeLeaf(...$filters['conditions'][1]),
            ]
        ));
});

test('ConditionTreeParser() should work with single condition without aggregator', function () {
    $collectionCategory = factoryConditionTreeParser();

    $filters = ['field' => 'id', 'operator' => 'Less_Than', 'value' => 'something'];

    expect(ConditionTreeParser::fromPLainObject($collectionCategory, $filters))
        ->toEqual(new ConditionTreeLeaf(...$filters));
});

test('ConditionTreeParser() should work with "IN" on a string', function () {
    $collectionCategory = factoryConditionTreeParser();

    $filters = ['field' => 'label', 'operator' => 'In', 'value' => ' id1,id2 , id3'];

    expect(ConditionTreeParser::fromPLainObject($collectionCategory, $filters))
        ->toEqual(new ConditionTreeLeaf(field: 'label', operator: 'In', value: ['id1', 'id2', 'id3']));
});

test('ConditionTreeParser() should work with "IN" on a number', function () {
    $collectionCategory = factoryConditionTreeParser();

    $filters = ['field' => 'id', 'operator' => 'In', 'value' => '1,2,3'];

    expect(ConditionTreeParser::fromPLainObject($collectionCategory, $filters))
        ->toEqual(new ConditionTreeLeaf(field: 'id', operator: 'In', value: [1, 2, 3]));
});

test('ConditionTreeParser() should work with "IN" on a boolean', function () {
    $collectionCategory = factoryConditionTreeParser();

    $filters = ['field' => 'active', 'operator' => 'In', 'value' => 'true,0,false,yes,no'];

    expect(ConditionTreeParser::fromPLainObject($collectionCategory, $filters))
        ->toEqual(new ConditionTreeLeaf(field: 'active', operator: 'In', value: [true, false, false, true, false]));
});
