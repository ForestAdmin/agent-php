<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
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
