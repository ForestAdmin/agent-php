<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils\Flattener;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils\Undefined;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

test('unflatten() should work with simple case', function () {
    $flatList = [
        [1, 2, 3],
        ['romain', new Undefined(), 'ana'],
    ];
    $projection = new Projection(['id', 'book:author:firstname']);

    expect(Flattener::unFlatten($flatList, $projection))->toEqual(
        [
            ['id' => 1, 'book' => ['author' => ['firstname' => 'romain']]],
            ['id' => 2],
            ['id' => 3, 'book' => ['author' => ['firstname' => 'ana']]],
        ]
    );
});

test('unflatten() should work with multiple undefined', function () {
    $flatList = [[new Undefined()], [15], [26], [new Undefined()]];
    $projection = new Projection(
        [
            'rental:customer:name',
            'rental:id',
            'rental:numberOfDays',
            'rental:customer:id',
        ]
    );

    expect(Flattener::unFlatten($flatList, $projection))->toEqual(
        [
            ['rental' => ['id' => 15, 'numberOfDays' => 26]],
        ]
    );
});

test('flatten() should work', function () {
    $records = [
        ['id' => 1, 'book' => ['author' => ['firstname' => 'romain']]],
        ['id' => 2, 'book' => new Undefined()],
        ['id' => 3, 'book' => ['author' => ['firstname' => 'ana']]],
    ];
    $projection = new Projection(['id', 'book:author:firstname']);

    expect(Flattener::flatten($records, $projection))->toEqual(
        [
            [1, 2, 3],
            ['romain', new Undefined(), 'ana'],
        ]
    );
});

test('round trip with markers should conserve null values', function () {
    $records = [
        ['id' => 1],
        ['id' => 2, 'book' => null],
        ['id' => 3, 'book' => ['author' => null]],
        ['id' => 4, 'book' => ['author' => ['firstname' => 'Isaac', 'lastname' => 'Asimov']]],
        ['id' => 5, 'book' => ['author' => ['firstname' => null, 'lastname' => null]]],
    ];

    $projection = new Projection(['id', 'book:author:firstname', 'book:author:lastname']);
    $projectionWithMarker = Flattener::withNullMarker($projection);
    $flattened = Flattener::flatten($records, $projectionWithMarker);
    $unFlattened = Flattener::unFlatten($flattened, $projectionWithMarker);

    expect($projectionWithMarker->toArray())->toEqual(
        ['id', 'book:author:firstname', 'book:author:lastname', 'book:__null_marker', 'book:author:__null_marker']
    )
        ->and($flattened)->toEqual(
            [
                [1, 2, 3, 4, 5],
                [new Undefined(), new Undefined(), new Undefined(), 'Isaac', null],
                [new Undefined(), new Undefined(), new Undefined(), 'Asimov', null],
                [new Undefined(), null, new Undefined(), new Undefined(), new Undefined()],
                [new Undefined(), new Undefined(), null, new Undefined(), new Undefined()],
            ]
        )
        ->and($unFlattened)->toEqual($records);
});
