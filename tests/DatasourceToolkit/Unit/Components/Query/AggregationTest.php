<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

test('validate() should throw if the operation value is not allowed', function () {
    expect(fn () => new Aggregation('Foo'))->toThrow(ForestException::class, '🌳🌳🌳 Aggregate operation Foo not allowed');
});

test('validate() should not throw if the operation is allowed', function () {
    new Aggregation('Count');
})->expectNotToPerformAssertions();

test('getProjection() should work with a null field and an empty groups', function () {
    $aggregation = new Aggregation('Count');

    expect($aggregation->getProjection())->toEqual(new Projection([]));
});

test('getProjection() should work with a field and a groups', function () {
    $aggregation = new Aggregation('Count', 'aggregateField', [['field' => 'groupField']]);

    expect($aggregation->getProjection())->toEqual(new Projection(['aggregateField', 'groupField']));
});

test('getOperation() should work ', function () {
    $aggregation = new Aggregation('Count');

    expect($aggregation->getOperation())->toEqual('Count');
});

test('getField() should work with a field', function () {
    $aggregation = new Aggregation('Count', 'aggregateField');

    expect($aggregation->getField())->toEqual('aggregateField');
});

test('getField() should return null with no field', function () {
    $aggregation = new Aggregation('Count');

    expect($aggregation->getField())->toBeNull();
});

test('getGroups() should work with no grouping', function () {
    $aggregation = new Aggregation('Count');

    expect($aggregation->getGroups())->toEqual([]);
});

test('getGroups() should work with groups', function () {
    $aggregation = new Aggregation('Count', null, [['field' => 'groupField']]);

    expect($aggregation->getGroups())->toEqual([['field' => 'groupField']]);
});

test('override() should work with one arg', function () {
    $aggregation = new Aggregation('Count');

    expect($aggregation->override(operation: 'Sum'))->toEqual(new Aggregation('Sum'));
});

test('override() should work with all args', function () {
    $aggregation = new Aggregation('Count');

    expect($aggregation->override(operation: 'Sum', field: 'aggregateField', groups: [['field' => 'groupField']]))
        ->toEqual(new Aggregation('Sum', 'aggregateField', [['field' => 'groupField']]));
});

test('apply() should work with records, timezone and limit null', function () {
    $aggregation = new Aggregation('Count');
    $records = [
        ['id' => 1],
        ['id' => 1],
        ['id' => 1],
        ['id' => 2],
        ['id' => 2],
        ['id' => 2],
    ];

    expect($aggregation->apply($records, 'Europe/Paris'))->toEqual([
        [
            'group' => [],
            'value' => 6,
        ],
    ]);
});

test('apply() should work with records, timezone and limit null on Avg operation', function () {
    $aggregation = new Aggregation('Avg', 'id');
    $records = [
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ];

    expect($aggregation->apply($records, 'Europe/Paris'))->toEqual([
        [
            'group' => [],
            'value' => 2,
        ],
    ]);
});

test('apply() should work with group field on year', function () {
    $aggregation = new Aggregation('Avg', 'field', [['field' => 'groupField', 'operation' => 'Year']]);
    $records = [
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 10, 'groupField' => '2022-05-01'],
        ['field' => 15, 'groupField' => '2022-01-02'],
        ['field' => 10, 'groupField' => '2023-07-11'],
    ];

    expect($aggregation->apply($records, 'Europe/Paris'))->toEqual([
        [
            'group' => [
                'groupField' => '2022-01-01',
            ],
            'value' => 10,
        ],
        [
            'group' => [
                'groupField' => '2023-01-01',
            ],
            'value' => 10,
        ],
    ]);
});

test('apply() should work with group field on month', function () {
    $aggregation = new Aggregation('Avg', 'field', [['field' => 'groupField', 'operation' => 'Month']]);
    $records = [
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 10, 'groupField' => '2022-01-02'],
        ['field' => 1, 'groupField' => '2023-07-11'],
    ];

    expect($aggregation->apply($records, 'Europe/Paris'))->toEqual([
        [
            'group' => [
                'groupField' => '2022-05-01',
            ],
            'value' => 5,
        ],
        [
            'group' => [
                'groupField' => '2022-01-01',
            ],
            'value' => 10,
        ],
        [
            'group' => [
                'groupField' => '2023-07-01',
            ],
            'value' => 1,
        ],
    ]);
});

test('apply() should work with group field on day', function () {
    $aggregation = new Aggregation('Avg', 'field', [['field' => 'groupField', 'operation' => 'Day']]);
    $records = [
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 10, 'groupField' => '2022-01-02'],
        ['field' => 1, 'groupField' => '2023-07-11'],
    ];

    expect($aggregation->apply($records, 'Europe/Paris'))->toEqual([
        [
            'group' => [
                'groupField' => '2022-05-01',
            ],
            'value' => 5,
        ],
        [
            'group' => [
                'groupField' => '2022-01-02',
            ],
            'value' => 10,
        ],
        [
            'group' => [
                'groupField' => '2023-07-11',
            ],
            'value' => 1,
        ],
    ]);
});

test('apply() should work with group field on day with limit', function () {
    $aggregation = new Aggregation('Avg', 'field', [['field' => 'groupField', 'operation' => 'Day']]);
    $records = [
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 10, 'groupField' => '2022-01-02'],
        ['field' => 1, 'groupField' => '2023-07-11'],
    ];

    expect($aggregation->apply($records, 'Europe/Paris', 2))->toEqual([
        [
            'group' => [
                'groupField' => '2022-05-01',
            ],
            'value' => 5,
        ],
        [
            'group' => [
                'groupField' => '2022-01-02',
            ],
            'value' => 10,
        ],
    ]);
});

test('apply() should work with group field on week', function () {
    $aggregation = new Aggregation('Avg', 'field', [['field' => 'groupField', 'operation' => 'Week']]);
    $records = [
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 5, 'groupField' => '2022-05-01'],
        ['field' => 10, 'groupField' => '2022-01-02'],
        ['field' => 1, 'groupField' => '2023-07-11'],
    ];

    expect($aggregation->apply($records, 'Europe/Paris'))->toEqual([
        [
            'group' => [
                'groupField' => '2022-05-01',
            ],
            'value' => 5,
        ],
        [
            'group' => [
                'groupField' => '2022-01-01',
            ],
            'value' => 10,
        ],
        [
            'group' => [
                'groupField' => '2023-07-01',
            ],
            'value' => 1,
        ],
    ]);
});

test('nest() should work ', function () {
    $aggregation = new Aggregation('Sum', 'aggregateField', [['field' => 'groupField', 'operation' => 'Week']]);

    expect($aggregation->nest('prefix'))
        ->toEqual(new Aggregation('Sum', 'prefix:aggregateField', [['field' => 'prefix:groupField', 'operation' => 'Week']]));
});

test('nest() should work with null prefix', function () {
    $aggregation = new Aggregation('Sum', 'aggregateField', [['field' => 'groupField', 'operation' => 'Week']]);

    expect($aggregation->nest(null))
        ->toEqual(new Aggregation('Sum', 'aggregateField', [['field' => 'groupField', 'operation' => 'Week']]));
});

test('toArray() should work', function () {
    $aggregation = new Aggregation('Sum', 'aggregateField', [['field' => 'groupField']]);

    expect($aggregation->toArray())
        ->toEqual(
            [
                'operation' => 'Sum',
                'field'     => 'aggregateField',
                'groups'    => [['field' => 'groupField']],
            ]
        );
});
