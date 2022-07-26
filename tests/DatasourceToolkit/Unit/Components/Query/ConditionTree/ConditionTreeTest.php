<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
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
