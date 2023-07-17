<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

test('override() should work', function () {
    $leaf = new ConditionTreeLeaf('column', Operators::GREATER_THAN, 0);
    $filter = new Filter(conditionTree: $leaf);
    $newLeaf = new ConditionTreeLeaf('column', Operators::LESS_THAN, 0);

    expect($filter->override(conditionTree: $newLeaf))
        ->toEqual(new Filter(conditionTree: $newLeaf));
});

test('nest() should work', function () {
    $leaf = new ConditionTreeLeaf('column', Operators::GREATER_THAN, 0);
    $filter = new Filter(conditionTree: $leaf);
    $nestedFilter = $filter->nest('prefix');

    expect($nestedFilter)->toEqual(
        new Filter(conditionTree: new ConditionTreeLeaf('prefix:column', Operators::GREATER_THAN, 0))
    );
});

test('nest() should crash with a segment', function () {
    $segmentFilter = new Filter(segment: 'someSegment');

    expect($segmentFilter->isNestable())->toBeFalse()
        ->and(fn () => $segmentFilter->nest('prefix'))
        ->toThrow(ForestException::class, 'Filter can\'t be nested');
});

test('getSegment() should work', function () {
    $segmentFilter = new Filter(segment: 'someSegment');

    expect($segmentFilter->getSegment())->toEqual('someSegment');
});

test('getSearch() should work', function () {
    $segmentFilter = new Filter(search: 'foo');

    expect($segmentFilter->getSearch())->toEqual('foo');
});

test('getSearchExtended() should work', function () {
    $segmentFilter = new Filter(searchExtended: true);

    expect($segmentFilter->getSearchExtended())->toBeTrue();
});
