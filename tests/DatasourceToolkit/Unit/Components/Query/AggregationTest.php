<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

test('getProjection() should work with a null field and an empty groups', function () {
    $aggregation = new Aggregation('count');

    expect($aggregation->getProjection())->toEqual(new Projection([]));
});

test('getProjection() should work with a field and a groups', function () {
    $aggregation = new Aggregation('count', 'aggregateField', [['field' => 'groupField']]);

    expect($aggregation->getProjection())->toEqual(new Projection(['aggregateField', 'groupField']));
});

test('getOperation() should work ', function () {
    $aggregation = new Aggregation('count');

    expect($aggregation->getOperation())->toEqual('count');
});

test('getField() should work with a field', function () {
    $aggregation = new Aggregation('count', 'aggregateField');

    expect($aggregation->getField())->toEqual('aggregateField');
});

test('getField() should return null with no field', function () {
    $aggregation = new Aggregation('count');

    expect($aggregation->getField())->toBeNull();
});

test('getGroups() should work with no grouping', function () {
    $aggregation = new Aggregation('count');

    expect($aggregation->getGroups())->toEqual([]);
});

test('getGroups() should work with groups', function () {
    $aggregation = new Aggregation('count', null, [['field' => 'groupField']]);

    expect($aggregation->getGroups())->toEqual([['field' => 'groupField']]);
});

test('override() should work with one arg', function () {
    $aggregation = new Aggregation('count');

    expect($aggregation->override(operation: 'sum'))->toEqual(new Aggregation('sum'));
});

test('override() should work with all args', function () {
    $aggregation = new Aggregation('count');

    expect($aggregation->override(operation: 'sum', field: 'aggregateField', groups: [['field' => 'groupField']]))
        ->toEqual(new Aggregation('sum', 'aggregateField', [['field' => 'groupField']]));
});

test('nest() should work ', function () {
    $aggregation = new Aggregation('sum', 'aggregateField', [['field' => 'groupField', 'operation' => 'Week']]);

    expect($aggregation->nest('prefix'))
        ->toEqual(new Aggregation('sum', 'prefix:aggregateField', [['field' => 'prefix:groupField', 'operation' => 'Week']]));
});

test('nest() should work with null prefix', function () {
    $aggregation = new Aggregation('sum', 'aggregateField', [['field' => 'groupField', 'operation' => 'Week']]);

    expect($aggregation->nest(null))
        ->toEqual(new Aggregation('sum', 'aggregateField', [['field' => 'groupField', 'operation' => 'Week']]));
});

test('toArray() should work', function () {
    $aggregation = new Aggregation('sum', 'aggregateField', [['field' => 'groupField']]);

    expect($aggregation->toArray())
        ->toEqual(
            [
                'operation'  => 'sum',
                'field'      => 'aggregateField',
                'groups'     => [['field' => 'groupField']],
            ]
        );
});
