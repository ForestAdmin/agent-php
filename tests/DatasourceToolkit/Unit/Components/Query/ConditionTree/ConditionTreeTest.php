<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Str;

dataset('conditionTreeBranch', function () {
    yield $tree = new ConditionTreeBranch('And', [
        new ConditionTreeLeaf('column1', 'Equal', true),
        new ConditionTreeLeaf('column2', 'Equal', true),
    ]);
});

test('replaceFields() should work', function (ConditionTreeBranch $tree) {
    expect($tree->replaceFields(fn ($field) => "$field:suffix"))
        ->toEqual(
            new ConditionTreeBranch('And', [
                new ConditionTreeLeaf('column1:suffix', 'Equal', true),
                new ConditionTreeLeaf('column2:suffix', 'Equal', true),
            ])
        );
})->with('conditionTreeBranch');

test('replaceLeafs() should work', function (ConditionTreeBranch $tree) {
    expect($tree->replaceLeafs(fn (ConditionTreeLeaf $leaf) => $leaf->override(['value' => ! $leaf->getValue()])))
        ->toEqual(
            new ConditionTreeBranch('And', [
                new ConditionTreeLeaf('column1', 'Equal', false),
                new ConditionTreeLeaf('column2', 'Equal', false),
            ])
        );
})->with('conditionTreeBranch');

test('someLeaf() should work', function (ConditionTreeBranch $tree) {
    expect($tree->someLeaf(fn (ConditionTreeLeaf $leaf)    => $leaf->getValue() === true))->toBe(true)
        ->and($tree->someLeaf(fn (ConditionTreeLeaf $leaf) => $leaf->getField() === 'column1'))->toBe(true)
        ->and($tree->someLeaf(fn (ConditionTreeLeaf $leaf) => Str::startsWith($leaf->getField(), 'something')))->toBe(false);
})->with('conditionTreeBranch');

test('useIntervalOperator() should return true', function () {
    $leaf = new ConditionTreeLeaf('column', 'Today', true);

    expect($leaf->useIntervalOperator())->toBe(true);
});

test('useIntervalOperator() should return false', function () {
    $leaf = new ConditionTreeLeaf('column', 'Equal', true);

    expect($leaf->useIntervalOperator())->toBe(false);
});

test('projection() should work', function (ConditionTreeBranch $tree) {
    expect($tree->getProjection())
        ->toEqual(new Projection(['column1', 'column2']));
})->with('conditionTreeBranch');

test('nest() should work', function (ConditionTreeBranch $tree) {
    expect($tree->nest('prefix'))->toEqual(
        new ConditionTreeBranch('And', [
            new ConditionTreeLeaf('prefix:column1', 'Equal', true),
            new ConditionTreeLeaf('prefix:column2', 'Equal', true),
        ])
    );
})->with('conditionTreeBranch');

test('unnest() should work', function (ConditionTreeBranch $tree) {
    expect($tree->nest('prefix')->unnest())->toEqual($tree);
})->with('conditionTreeBranch');

test('unnest() should throw', function (ConditionTreeBranch $tree) {
    expect(static fn () => $tree->unnest())
            ->toThrow(ForestException::class, '🌳🌳🌳 Cannot unnest condition tree.');
})->with('conditionTreeBranch');

test('inverse() should work', function (ConditionTreeBranch $tree) {
    expect($tree->inverse())->toEqual(
        new ConditionTreeBranch('Or', [
            new ConditionTreeLeaf('column1', 'NotEqual', true),
            new ConditionTreeLeaf('column2', 'NotEqual', true),
        ])
    )
        ->and($tree->inverse()->inverse())->toEqual($tree);
})->with('conditionTreeBranch');

test('inverse() should work with blank', function () {
    $tree = new ConditionTreeLeaf('column1', 'Blank');
    expect($tree->inverse())->toEqual(new ConditionTreeLeaf('column1', 'Present'))
        ->and($tree->inverse()->inverse())->toEqual($tree);
});

test('inverse() should crash with unsupported operator', function () {
    $tree = new ConditionTreeLeaf('column1', 'Today');
    expect(fn () => $tree->inverse())
        ->toThrow(ForestException::class, '🌳🌳🌳 Operator: Today cannot be inverted.');
});

test('everyLeaf() should work', function (ConditionTreeBranch $tree) {
    expect($tree->everyLeaf(fn ($leaf) => $leaf->getField() === 'column1'))->toBe(false);
})->with('conditionTreeBranch');

test('forEachLeaf() should work', function (ConditionTreeBranch $tree) {
    expect(
        $tree->forEachLeaf(fn ($leaf) => $leaf->override(['field' => 'field']))
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('field', 'Equal', true),
                new ConditionTreeLeaf('field', 'Equal', true),
            ]
        )
    );
})->with('conditionTreeBranch');
