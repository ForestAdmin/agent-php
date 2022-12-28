<?php


use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils\Flattener;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

test('unflatten() should work with simple case', function () {
    $flatList = [
        [1, 2, 3],
        ['romain', null, 'ana'],
    ];
    $projection = new Projection(['id', 'book:author:firstname']);

    expect(Flattener::unFlatten($flatList, $projection))->toEqual(
        [
            ['id' => 1, 'book' => ['author' => ['firstname' => 'romain']]],
            ['id' => 2, 'book' => null],
            ['id' => 3, 'book' => ['author' => ['firstname' => 'ana']]],
        ]
    );
});

test('unflatten() should work with multiple null', function () {
    $flatList = [[null], [15], [26], [null]];
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
            ['rental' => ['id' => 15, 'numberOfDays' => 26, 'customer' => null]],
        ]
    );
});

test('flatten() should work', function () {
    $records = [
        ['id' => 1, 'book' => ['author' => ['firstname' => 'romain']]],
        ['id' => 2, 'book' => null],
        ['id' => 3, 'book' => ['author' => ['firstname' => 'ana']]],
    ];
    $projection = new Projection(['id', 'book:author:firstname']);

    expect(Flattener::flatten($records, $projection))->toEqual(
        [
            [1, 2, 3],
            ['romain', null, 'ana'],
        ]
    );
});
