<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

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

test('matchIds() with a collection which does not support equal and in should raise error', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
        ]
    );

    expect(static fn () => ConditionTreeFactory::matchIds($collection, [[]]))
        ->toThrow(ForestException::class, "🌳🌳🌳 Field 'id' must support operators: ['Equal', 'In']");
});

test('matchRecords() should generate matchNone with simple PK', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
        ]
    );

    expect(ConditionTreeFactory::matchRecords($collection, []))
        ->toEqual(new ConditionTreeBranch('Or', []));
});

test('matchRecords() should generate equal with simple PK', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
        ]
    );

    expect(ConditionTreeFactory::matchRecords($collection, [['id' => 1]]))
        ->toEqual(new ConditionTreeLeaf('id', 'Equal', 1));
});

test('matchRecords() should generate In with simple PK', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
        ]
    );

    expect(ConditionTreeFactory::matchRecords($collection, [['id' => 1], ['id' => 2]]))
        ->toEqual(new ConditionTreeLeaf('id', 'In', [1, 2]));
});

test('matchRecords() should generate a simple and with a composite PK', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'col1' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
            'col2' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
        ]
    );

    expect(
        ConditionTreeFactory::matchRecords(
            $collection,
            [
                [
                    'col1' => 1,
                    'col2' => 1,
                ],
            ]
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('col1', 'Equal', 1),
                new ConditionTreeLeaf('col2', 'Equal', 1),
            ]
        )
    );
});

test('matchRecords() should factorize with a composite PK', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'col1' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
            'col2' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
        ]
    );

    expect(
        ConditionTreeFactory::matchRecords(
            $collection,
            [
                [
                    'col1' => 1,
                    'col2' => 1,
                ],
                [
                    'col1' => 1,
                    'col2' => 2,
                ],
            ]
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('col1', 'Equal', 1),
                new ConditionTreeLeaf('col2', 'In', [1,2]),
            ]
        )
    );
});

test('matchRecords() should not factorize with a composite PK', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'col1' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
            'col2' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
        ]
    );

    expect(
        ConditionTreeFactory::matchRecords(
            $collection,
            [
                [
                    'col1' => 1,
                    'col2' => 1,
                ],
                [
                    'col1' => 2,
                    'col2' => 2,
                ],
            ]
        )
    )->toEqual(
        new ConditionTreeBranch(
            'Or',
            [
                new ConditionTreeBranch(
                    'And',
                    [
                        new ConditionTreeLeaf('col1', 'Equal', 1),
                        new ConditionTreeLeaf('col2', 'Equal', 1),
                    ]
                ),
                new ConditionTreeBranch(
                    'And',
                    [
                        new ConditionTreeLeaf('col1', 'Equal', 2),
                        new ConditionTreeLeaf('col2', 'Equal', 2),
                    ]
                ),
            ]
        )
    );
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
