<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Str;

dataset('conditionTreeBranch', function () {
    yield $tree = new ConditionTreeBranch('And', [
        new ConditionTreeLeaf('column1', Operators::EQUAL, true),
        new ConditionTreeLeaf('column2', Operators::EQUAL, true),
    ]);
});

test('replaceFields() should work', function (ConditionTreeBranch $tree) {
    expect($tree->replaceFields(fn ($field) => "$field:suffix"))
        ->toEqual(
            new ConditionTreeBranch('And', [
                new ConditionTreeLeaf('column1:suffix', Operators::EQUAL, true),
                new ConditionTreeLeaf('column2:suffix', Operators::EQUAL, true),
            ])
        );
})->with('conditionTreeBranch');

test('replaceLeafs() should work', function (ConditionTreeBranch $tree) {
    expect($tree->replaceLeafs(fn (ConditionTreeLeaf $leaf) => $leaf->override(value: ! $leaf->getValue())))
        ->toEqual(
            new ConditionTreeBranch('And', [
                new ConditionTreeLeaf('column1', Operators::EQUAL, false),
                new ConditionTreeLeaf('column2', Operators::EQUAL, false),
            ])
        );
})->with('conditionTreeBranch');

test('someLeaf() should work', function (ConditionTreeBranch $tree) {
    expect($tree->someLeaf(fn (ConditionTreeLeaf $leaf) => $leaf->getValue() === true))->toBe(true)
        ->and($tree->someLeaf(fn (ConditionTreeLeaf $leaf) => $leaf->getField() === 'column1'))->toBe(true)
        ->and($tree->someLeaf(fn (ConditionTreeLeaf $leaf) => Str::startsWith($leaf->getField(), 'something')))->toBe(false);
})->with('conditionTreeBranch');

test('useIntervalOperator() should return true', function () {
    $leaf = new ConditionTreeLeaf('column', Operators::TODAY, true);

    expect($leaf->useIntervalOperator())->toBe(true);
});

test('useIntervalOperator() should return false', function () {
    $leaf = new ConditionTreeLeaf('column', Operators::EQUAL, true);

    expect($leaf->useIntervalOperator())->toBe(false);
});

test('projection() should work', function (ConditionTreeBranch $tree) {
    expect($tree->getProjection())
        ->toEqual(new Projection(['column1', 'column2']));
})->with('conditionTreeBranch');

test('nest() should work', function (ConditionTreeBranch $tree) {
    expect($tree->nest('prefix'))->toEqual(
        new ConditionTreeBranch('And', [
            new ConditionTreeLeaf('prefix:column1', Operators::EQUAL, true),
            new ConditionTreeLeaf('prefix:column2', Operators::EQUAL, true),
        ])
    );
})->with('conditionTreeBranch');

test('unnest() should work with conditionTreeBranch', function (ConditionTreeBranch $tree) {
    expect($tree->nest('prefix')->unnest())->toEqual($tree);
})->with('conditionTreeBranch');

test('unnest() should work with conditionTreeLeaf', function (ConditionTreeBranch $tree) {
    $tree = $tree->nest('prefix');
    $conditionTreeLeaf = $tree->getConditions()[0];

    expect($conditionTreeLeaf->unnest())
        ->toEqual(new ConditionTreeLeaf('column1', Operators::EQUAL, true));
})->with('conditionTreeBranch');

test('unnest() should throw', function (ConditionTreeBranch $tree) {
    expect(static fn () => $tree->unnest())
            ->toThrow(ForestException::class, '🌳🌳🌳 Cannot unnest condition tree.');
})->with('conditionTreeBranch');

test('inverse() should work', function (ConditionTreeBranch $tree) {
    expect($tree->inverse())->toEqual(
        new ConditionTreeBranch('Or', [
            new ConditionTreeLeaf('column1', Operators::NOT_EQUAL, true),
            new ConditionTreeLeaf('column2', Operators::NOT_EQUAL, true),
        ])
    )
        ->and($tree->inverse()->inverse())->toEqual($tree);
})->with('conditionTreeBranch');

test('inverse() should work with blank', function () {
    $tree = new ConditionTreeLeaf('column1', Operators::BLANK);
    expect($tree->inverse())->toEqual(new ConditionTreeLeaf('column1', Operators::PRESENT))
        ->and($tree->inverse()->inverse())->toEqual($tree);
});

test('inverse() should crash with unsupported operator', function () {
    $tree = new ConditionTreeLeaf('column1', Operators::TODAY);
    expect(fn () => $tree->inverse())
        ->toThrow(ForestException::class, '🌳🌳🌳 Operator: Today cannot be inverted.');
});

test('everyLeaf() should work', function (ConditionTreeBranch $tree) {
    expect($tree->everyLeaf(fn ($leaf) => $leaf->getField() === 'column1'))->toBe(false);
})->with('conditionTreeBranch');

test('forEachLeaf() should work', function (ConditionTreeBranch $tree) {
    expect(
        $tree->forEachLeaf(fn ($leaf) => $leaf->override(field: 'field'))
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('field', Operators::EQUAL, true),
                new ConditionTreeLeaf('field', Operators::EQUAL, true),
            ]
        )
    );
})->with('conditionTreeBranch');

test('validOperator() should throw', function () {
    expect(static fn () => new ConditionTreeLeaf('column1', 'unknown'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Invalid operators, the unknown operator does not exist.');
});

test('match() should work', function (ConditionTreeBranch $tree) {
    $collection = new Collection(new Datasource(), 'myCollection');
    $collection->addFields(
        [
            'column1' => new ColumnSchema(
                columnType: PrimitiveType::BOOLEAN,
                filterOperators: [Operators::EQUAL],
            ),
            'column2' => new ColumnSchema(
                columnType: PrimitiveType::BOOLEAN,
                filterOperators: [Operators::EQUAL],
            ),
        ]
    );

    expect($tree->match(['column1' => true, 'column2' => true], $collection, 'Europe/Paris'))->toBeTrue()
        ->and($tree->match(['column1' => true, 'column2' => false], $collection, 'Europe/Paris'))->toBeFalse()
        ->and($tree->inverse()->match(['column1' => true, 'column2' => true], $collection, 'Europe/Paris'))->toBeFalse()
        ->and($tree->inverse()->match(['column1' => true, 'column2' => false], $collection, 'Europe/Paris'))->toBeTrue();
})->with('conditionTreeBranch');

test('match() should work with many operators', function () {
    $collection = new Collection(new Datasource(), 'myCollection');
    $collection->addFields(
        [
            'string' => new ColumnSchema(
                columnType: PrimitiveType::STRING,
                filterOperators: [Operators::EQUAL],
            ),
            'array'  => new ColumnSchema(
                columnType: [PrimitiveType::STRING],
                filterOperators: [Operators::EQUAL],
            ),
        ]
    );

    $allConditions = new ConditionTreeBranch('And', [
        new ConditionTreeLeaf('string', Operators::PRESENT),
        new ConditionTreeLeaf('string', Operators::MATCH, '%value%'),
        new ConditionTreeLeaf('string', Operators::LESS_THAN, 'valuf'),
        new ConditionTreeLeaf('string', Operators::EQUAL, 'value'),
        new ConditionTreeLeaf('string', Operators::GREATER_THAN, 'valud'),
        new ConditionTreeLeaf('string', Operators::IN, ['value']),
        new ConditionTreeLeaf('array', Operators::INCLUDES_ALL, ['value']),
        new ConditionTreeLeaf('string', Operators::LONGER_THAN, 0),
        new ConditionTreeLeaf('string', Operators::SHORTER_THAN, 999),
        new ConditionTreeLeaf('string', Operators::STARTS_WITH, 'val'),
        new ConditionTreeLeaf('string', Operators::ENDS_WITH, 'lue'),
    ]);


    expect($allConditions->match(['string' => 'value', 'array' => ['value']], $collection, 'Europe/Paris'))->toBeTrue();
});

test('match() should work with null value', function () {
    $collection = new Collection(new Datasource(), 'myCollection');
    $collection->addFields(
        [
            'string' => new ColumnSchema(
                columnType: PrimitiveType::STRING,
                filterOperators: [Operators::EQUAL],
            ),
        ]
    );
    $leaf = new ConditionTreeLeaf('string', Operators::MATCH, '%value%');

    expect($leaf->match(['string' => null], $collection, 'Europe/Paris'))->toBeFalse();
});

test('apply() should work', function (ConditionTreeBranch $tree) {
    $collection = new Collection(new Datasource(), 'myCollection');
    $collection->addFields(
        [
            'column1' => new ColumnSchema(
                columnType: PrimitiveType::BOOLEAN,
                filterOperators: [Operators::EQUAL],
            ),
            'column2' => new ColumnSchema(
                columnType: PrimitiveType::BOOLEAN,
                filterOperators: [Operators::EQUAL],
            ),
        ]
    );
    $records = [
        ['id' => 1, 'column1' => true, 'column2' => true],
        ['id' => 2, 'column1' => false, 'column2' => true],
        ['id' => 3, 'column1' => true, 'column2' => false],
    ];

    expect($tree->apply($records, $collection, 'Europe/Paris'))->toEqual(
        [
            ['id' => 1, 'column1' => true, 'column2' => true],
        ]
    );
})->with('conditionTreeBranch');
