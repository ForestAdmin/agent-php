<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

dataset('simpleFilter', function () {
    $leaf = new ConditionTreeLeaf('column', 'GreaterThan', 0);
    yield $filter = new Filter(conditionTree: $leaf);
});

test('override() should work', function (Filter $filter) {
    $newLeaf = new ConditionTreeLeaf('column', 'LessThan', 0);

    expect($filter->override(conditionTree: $newLeaf))
        ->toEqual(new Filter(conditionTree: $newLeaf));
})->with('simpleFilter');

test('nest() should work', function (Filter $filter) {
    $nestedFilter = $filter->nest('prefix');

    expect($nestedFilter)->toEqual(
        new Filter(conditionTree: new ConditionTreeLeaf('prefix:column', 'GreaterThan', 0))
    );
})->with('simpleFilter');


test('nest() should crash with a segment', function () {
    $segmentFilter = new Filter(segment: 'someSegment');

    expect($segmentFilter->isNestable())->toBeFalse()
        ->and(fn () => $segmentFilter->nest('prefix'))
        ->toThrow(ForestException::class, 'Filter can\'t be nested');
});
