<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms\Pattern;

test('Contains should be rewritten', function () {
    $comparisons = Pattern::patternTransforms();
    expect($comparisons[Operators::CONTAINS][0]['replacer'](new ConditionTreeLeaf('column', Operators::CONTAINS, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::LIKE, '%something%'));
});

test('StartsWith should be rewritten', function () {
    $comparisons = Pattern::patternTransforms();
    expect($comparisons[Operators::STARTS_WITH][0]['replacer'](new ConditionTreeLeaf('column', Operators::STARTS_WITH, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::LIKE, 'something%'));
});

test('EndsWith should be rewritten', function () {
    $comparisons = Pattern::patternTransforms();
    expect($comparisons[Operators::ENDS_WITH][0]['replacer'](new ConditionTreeLeaf('column', Operators::ENDS_WITH, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::LIKE, '%something'));
});

test('IContains should be rewritten', function () {
    $comparisons = Pattern::patternTransforms();
    expect($comparisons[Operators::ICONTAINS][0]['replacer'](new ConditionTreeLeaf('column', Operators::CONTAINS, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::ILIKE, '%something%'));
});

test('IStartsWith should be rewritten', function () {
    $comparisons = Pattern::patternTransforms();
    expect($comparisons[Operators::ISTARTS_WITH][0]['replacer'](new ConditionTreeLeaf('column', Operators::STARTS_WITH, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::ILIKE, 'something%'));
});

test('IEndsWith should be rewritten', function () {
    $comparisons = Pattern::patternTransforms();
    expect($comparisons[Operators::IENDS_WITH][0]['replacer'](new ConditionTreeLeaf('column', Operators::ENDS_WITH, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::ILIKE, '%something'));
});

test('Like should be rewritten', function () {
    $comparisons = Pattern::patternTransforms();

    expect($comparisons[Operators::LIKE][0]['replacer'](new ConditionTreeLeaf('column', Operators::EQUAL, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::MATCH, '/^something$/'));
});

test('ILike should be rewritten', function () {
    $comparisons = Pattern::patternTransforms();

    expect($comparisons[Operators::ILIKE][0]['replacer'](new ConditionTreeLeaf('column', Operators::EQUAL, 'something'), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::MATCH, '/^something$/i'));
});
