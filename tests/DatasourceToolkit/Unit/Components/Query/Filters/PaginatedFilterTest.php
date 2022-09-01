<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

dataset('paginatedFilter', function () {
    yield $paginatedFilter = new PaginatedFilter(
        conditionTree: new ConditionTreeLeaf('column', Operators::GREATER_THAN, 0),
        sort: new Sort(['column']),
        page: new Page(0, 20),
    );
});

test('override() should work', function (PaginatedFilter $paginatedFilter) {
    $newLeaf = new ConditionTreeLeaf('column', Operators::LESS_THAN, 0);
    $newFilter = $paginatedFilter->override(
        conditionTree: $newLeaf,
        page: new Page(0, 10),
        sort: new Sort(['column2']),
    );

    expect($newFilter)
        ->toEqual(new PaginatedFilter(
            conditionTree: $newLeaf,
            sort: new Sort(['column2']),
            page: new Page(0, 10)
        ));
})->with('paginatedFilter');

test('nest() should work', function (PaginatedFilter $paginatedFilter) {
    $nestedFilter = $paginatedFilter->nest('prefix');
    expect($paginatedFilter->isNestable())->toBeTrue()
        ->and($nestedFilter)
        ->toEqual(
            new PaginatedFilter(
                conditionTree: new ConditionTreeLeaf('prefix:column', Operators::GREATER_THAN, 0),
                sort: new Sort(['prefix:column']),
                page: new Page(0, 20),
            )
        );
})->with('paginatedFilter');

test('nest() should crash with a segment', function () {
    $segmentFilter = new PaginatedFilter(segment: 'someSegment');

    expect($segmentFilter->isNestable())->toBeFalse()
        ->and(fn () => $segmentFilter->nest('prefix'))
        ->toThrow(ForestException::class, 'Filter can\'t be nested');
});
