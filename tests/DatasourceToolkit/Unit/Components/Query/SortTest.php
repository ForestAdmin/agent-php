<?php


use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort\SortFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

test('new sort should work', function () {
    $sort = new Sort(
        [
            ['field' => 'column1', 'ascending' => true],
            ['field' => 'column2', 'ascending' => false],
        ]
    );

    expect($sort->toArray())->toEqual(
        [
            [
                'field'     => 'column1',
                'ascending' => true,
            ],
            [
                'field'     => 'column2',
                'ascending' => false,
            ],
        ]
    );
});

test('getProjection() should work', function () {
    $sort = new Sort(
        [
            ['field' => 'column1', 'ascending' => true],
            ['field' => 'column2', 'ascending' => false],
        ]
    );

    expect($sort->getProjection())->toEqual(new Projection(['column1', 'column2']));
});


test('nest should work', function () {
    $sort = new Sort(
        [
            ['field' => 'column1', 'ascending' => true],
            ['field' => 'column2', 'ascending' => false],
        ]
    );

    expect($sort->nest('prefix')->toArray())->toEqual(
        [
            [
                'field'     => 'prefix:column1',
                'ascending' => true,
            ],
            [
                'field'     => 'prefix:column2',
                'ascending' => false,
            ],
        ]
    );
});

test('unnest should work', function () {
    $sort = new Sort(
        [
            ['field' => 'column1', 'ascending' => true],
            ['field' => 'column2', 'ascending' => false],
        ]
    );

    expect($sort->nest('prefix')->unnest()->toArray())->toEqual(
        [
            [
                'field'     => 'column1',
                'ascending' => true,
            ],
            [
                'field'     => 'column2',
                'ascending' => false,
            ],
        ]
    );
});

test('unnest should throw', function () {
    $sort = new Sort(
        [
            ['field' => 'prefix:column1', 'ascending' => true],
            ['field' => 'column2', 'ascending' => false],
        ]
    );

    expect(fn () => $sort->unnest())->toThrow(ForestException::class, '🌳🌳🌳 Cannot unnest sort.');
});

test('inverse should work', function () {
    $sort = new Sort(
        [
            ['field' => 'column1', 'ascending' => true],
            ['field' => 'column2', 'ascending' => false],
        ]
    );

    expect($sort->inverse()->toArray())->toEqual(
        [
            [
                'field'     => 'column1',
                'ascending' => false,
            ],
            [
                'field'     => 'column2',
                'ascending' => true,
            ],
        ]
    );
});

test('apply() should sort records', function () {
    $sort = new Sort(
        [
            ['field' => 'column1', 'ascending' => true],
            ['field' => 'column2', 'ascending' => false],
        ]
    );
    $records = [
      [ 'column1' => 2, 'column2' => 2 ],
      [ 'column1' => 1, 'column2' => 1 ],
      [ 'column1' => 1, 'column2' => 1 ],
      [ 'column1' => 1, 'column2' => 2 ],
      [ 'column1' => 2, 'column2' => 1 ],
    ];

    expect(array_values($sort->apply($records)))
        ->toEqual(
            [
                [ 'column1' => 1, 'column2' => 2 ],
                [ 'column1' => 1, 'column2' => 1 ],
                [ 'column1' => 1, 'column2' => 1 ],
                [ 'column1' => 2, 'column2' => 2 ],
                [ 'column1' => 2, 'column2' => 1 ],
            ]
        );
});


test('SortFactory::byPrimaryKeys should work', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    expect(SortFactory::byPrimaryKeys($collection))->toEqual(new Sort([['field' => 'id', 'ascending' => true]]));
});

test('construct() should throw if there is no key field key in a record', function () {
    expect(fn () => new Sort(
        [
            ['label' => 'column1', 'ascending' => true],
        ]
    ))->toThrow(ForestException::class, '🌳🌳🌳 Invalid sort clause, key "field" and "ascending" must be present and should be of type string and boolean.');
});

test('construct() should throw if there is no ascending key in a record', function () {
    expect(fn () => new Sort(
        [
            ['field' => 'column1'],
        ]
    ))->toThrow(ForestException::class, '🌳🌳🌳 Invalid sort clause, key "field" and "ascending" must be present and should be of type string and boolean.');
});

test('construct() should throw if there is the value of key is not a string in a record', function () {
    expect(fn () => new Sort(
        [
            ['field' => 1, 'ascending' => true],
        ]
    ))->toThrow(ForestException::class, '🌳🌳🌳 Invalid sort clause, key "field" and "ascending" must be present and should be of type string and boolean.');
});

test('construct() should throw if there is the value of ascending is not a boolean in a record', function () {
    expect(fn () => new Sort(
        [
            ['field' => 'column1', 'ascending' => 'true'],
        ]
    ))->toThrow(ForestException::class, '🌳🌳🌳 Invalid sort clause, key "field" and "ascending" must be present and should be of type string and boolean.');
});
