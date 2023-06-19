<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms\Comparisons;

test('Blank should be rewritten for strings', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::BLANK][0]['replacer'](new ConditionTreeLeaf('column', Operators::BLANK), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::IN, [null, '']));
});

test('Blank should be rewritten for other types', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::BLANK][1]['replacer'](new ConditionTreeLeaf('column', Operators::BLANK), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::MISSING));
});

test('Missing should be rewritten', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::MISSING][0]['replacer'](new ConditionTreeLeaf('column', Operators::MISSING), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::EQUAL, null));
});

test('Present should be rewritten for strings', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::PRESENT][0]['replacer'](new ConditionTreeLeaf('column', Operators::PRESENT), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::NOT_IN, [null, '']));
});

test('Present should be rewritten for other types', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::PRESENT][1]['replacer'](new ConditionTreeLeaf('column', Operators::PRESENT), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::NOT_EQUAL, null));
});

test('Equal should be rewritten', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::EQUAL][0]['replacer'](new ConditionTreeLeaf('column', Operators::EQUAL, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::IN, ['something']));
});

test('In should be rewritten with one element', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::IN][0]['replacer'](new ConditionTreeLeaf('column', Operators::IN, ['something', 'else']), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::MATCH, '/(something|else)/g'));
});

test('In should be rewritten with multiple elements', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::IN][0]['replacer'](new ConditionTreeLeaf('column', Operators::IN, [null, 'something', 'else']), 'Europe/Paris'))
        ->toEqual(new ConditionTreeBranch(
            'Or',
            [
                new ConditionTreeLeaf('column', Operators::EQUAL, null),
                new ConditionTreeLeaf('column', Operators::MATCH, '/(something|else)/g'),
            ]
        ));
});

test('NotEqual should be rewritten', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::NOT_EQUAL][0]['replacer'](new ConditionTreeLeaf('column', Operators::NOT_EQUAL, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::NOT_IN, ['something']));
});

test('NotIn should be rewritten with one element', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::NOT_IN][0]['replacer'](new ConditionTreeLeaf('column', Operators::NOT_IN, ['something']), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::MATCH, '/(?!something)/g'));
});

test('NotIn should be rewritten with multiple elements', function () {
    $comparisons = Comparisons::equalityTransforms();
    expect($comparisons[Operators::NOT_IN][0]['replacer'](new ConditionTreeLeaf('column', Operators::NOT_IN, ['something', 'else']), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::MATCH, '/(?!something|else)/g'));
});
