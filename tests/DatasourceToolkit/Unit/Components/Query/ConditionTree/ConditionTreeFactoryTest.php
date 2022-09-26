<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

test('intersect() should return the parameter when called with only one param', function () {
    $tree = ConditionTreeFactory::intersect([new ConditionTreeLeaf('column', Operators::EQUAL, true)]);

    expect($tree)->toEqual(new ConditionTreeLeaf('column', Operators::EQUAL, true));
});

test('intersect() should ignore null params', function () {
    $tree = ConditionTreeFactory::intersect([null, new ConditionTreeLeaf('column', Operators::EQUAL, true), null]);

    expect($tree)->toEqual(new ConditionTreeLeaf('column', Operators::EQUAL, true));
});

test('intersect() multiple trees should return the tree', function () {
    $conditionTree = new ConditionTreeLeaf('column', Operators::EQUAL, true);
    $otherConditionTree = new ConditionTreeLeaf('otherColumn', Operators::EQUAL, true);
    $tree = ConditionTreeFactory::intersect([$conditionTree, $otherConditionTree]);

    expect($tree)->toEqual(new ConditionTreeBranch('And', [$conditionTree, $otherConditionTree]));
});

test('intersect() should merge And trees', function () {
    $conditionTree = new ConditionTreeLeaf('column', Operators::EQUAL, true);
    $otherConditionTree = new ConditionTreeLeaf('otherColumn', Operators::EQUAL, true);
    $tree = ConditionTreeFactory::intersect(
        [
            new ConditionTreeBranch('And', [$conditionTree]),
            new ConditionTreeBranch('And', [$otherConditionTree]),
        ]
    );

    expect($tree)->toEqual(new ConditionTreeBranch('And', [$conditionTree, $otherConditionTree]));
});

test('matchIds() with a collection with no pk should raise error', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(['column' => new ColumnSchema(columnType: PrimitiveType::STRING)]);

    expect(static fn () => ConditionTreeFactory::matchIds($collection, [[]]))
        ->toThrow(ForestException::class, 'Collection must have at least one primary key');
});

test('fromArray() should crash when calling with badly formatted array', function () {
    expect(static fn () => ConditionTreeFactory::fromArray([]))
        ->toThrow(ForestException::class, '🌳🌳🌳 Failed to instantiate condition tree from array');
});

test('fromArray() should work with a simple case', function () {
    $tree = ConditionTreeFactory::fromArray(
        [
            'field'    => 'field',
            'operator' => Operators::EQUAL,
            'value'    => 'something',
        ]
    );

    expect($tree)->toEqual(new ConditionTreeLeaf('field', Operators::EQUAL, 'something'));
});

test('fromArray() should remove useless aggregators from the frontend', function () {
    $tree = ConditionTreeFactory::fromArray(
        [
            'aggregator' => 'And',
            'conditions' => [
                [
                    'field'    => 'field',
                    'operator' => Operators::EQUAL,
                    'value'    => 'something',
                ],
            ],
        ]
    );

    expect($tree)->toEqual(new ConditionTreeLeaf('field', Operators::EQUAL, 'something'));
});

test('fromArray() should work with an aggregator', function () {
    $tree = ConditionTreeFactory::fromArray(
        [
            'aggregator' => 'And',
            'conditions' => [
                [
                    'field'    => 'field',
                    'operator' => Operators::EQUAL,
                    'value'    => 'something',
                ],
                [
                    'field'    => 'field',
                    'operator' => Operators::EQUAL,
                    'value'    => 'something',
                ],
            ],
        ]
    );

    expect($tree)->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('field', Operators::EQUAL, 'something'),
                new ConditionTreeLeaf('field', Operators::EQUAL, 'something'),
            ]
        ),
    );
});
