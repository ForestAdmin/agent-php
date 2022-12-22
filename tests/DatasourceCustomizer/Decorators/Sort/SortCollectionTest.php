<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Sort\SortCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;

function factorySortCollection()
{
    $records = [
        [
            'id'     => 1,
            'author' => ['id' => 1, 'firstName' => 'Isaac', 'lastName' => 'Asimov'],
            'title'  => 'Foundation',
        ],
        [
            'id'     => 3,
            'author' => ['id' => 3, 'firstName' => 'Roberto', 'lastName' => 'Saviano'],
            'title'  => 'Gomorrah',
        ],
        [
            'id'     => 2,
            'author' => ['id' => 2, 'firstName' => 'Edward O.', 'lastName' => 'Thorp'],
            'title'  => 'Beat the dealer',
        ],
    ];

    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'year'   => new ColumnSchema(columnType: PrimitiveType::STRING, isSortable: true),
            'title'  => new ColumnSchema(columnType: PrimitiveType::STRING, isSortable: false),
            'author' => new ManyToOneSchema(
                foreignKey: 'author_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
        ]
    );
    $collectionBook = mock($collectionBook)
        ->makePartial()
        ->shouldReceive('list')
        ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type(Projection::class))
        ->andReturn($records)
        ->getMock();

    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionUser);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'schemaPath'   => sys_get_temp_dir() . '/.forestadmin-schema.json',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
        'agentUrl'     => 'http://localhost/',
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    $caller = new Caller(
        id: 1,
        email: 'sarah.connor@skynet.com',
        firstName: 'sarah',
        lastName: 'connor',
        team: 'survivor',
        renderingId: 1,
        tags: [],
        timezone: 'Europe/Paris',
        permissionLevel: 'admin',
        role: 'dev'
    );

    return [$datasource, $collectionBook, $caller, $records];
}

function factorySortChildCollection($records)
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'year'   => new ColumnSchema(columnType: PrimitiveType::STRING, isSortable: true),
            'title'  => new ColumnSchema(columnType: PrimitiveType::STRING, isSortable: false),
            'author' => new ManyToOneSchema(
                foreignKey: 'author_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
        ]
    );

    $collectionBook = mock($collectionBook)
        ->makePartial()
        ->shouldReceive('list')
        ->andReturn($records[0], $records[1])
        ->getMock();

    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionUser);

    $datasourceDecorator = new DatasourceDecorator($datasource, SortCollection::class);
    $datasourceDecorator->build();

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'schemaPath'   => sys_get_temp_dir() . '/.forestadmin-schema.json',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
        'agentUrl'     => 'http://localhost/',
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    $caller = new Caller(
        id: 1,
        email: 'sarah.connor@skynet.com',
        firstName: 'sarah',
        lastName: 'connor',
        team: 'survivor',
        renderingId: 1,
        tags: [],
        timezone: 'Europe/Paris',
        permissionLevel: 'admin',
        role: 'dev'
    );

    return [$datasourceDecorator, $caller];
}

test('emulateFieldSorting() should return the field sortable to null', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);
    $sortCollection->emulateFieldSorting('year');

    expect(invokeProperty($sortCollection, 'sorts')['year'])->toBeNull();
});

test('emulateFieldSorting() should throw when the field doesn\'t exist', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);

    expect(fn () => $sortCollection->emulateFieldSorting('afield'))->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Book.afield');
});

test('replaceFieldSorting() should return the field sortable to null', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);
    $sortCollection->replaceFieldSorting('year', null);

    expect(invokeProperty($sortCollection, 'sorts')['year'])->toBeNull();
});

test('replaceFieldSorting() should work when equivalentSort is not empty', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);
    $sortCollection->replaceFieldSorting(
        'id',
        [
            ['field' => 'title', 'ascending' => true],
        ]
    );

    expect(invokeProperty($sortCollection, 'sorts')['id'])->toEqual(new Sort([['field' => 'title', 'ascending' => true]]));
});

test('replaceFieldSorting() should throw if the field is a relation', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);

    expect(fn () => $sortCollection->replaceFieldSorting(
        'author',
        [
            ['field' => 'id', 'ascending' => true],
        ]
    ))->toThrow(ForestException::class, "🌳🌳🌳 Unexpected field type: Book.author (found ManyToOne expected 'Column')");
});

test('replaceFieldSorting() should throw if the field is in a relation', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);

    expect(fn () => $sortCollection->replaceFieldSorting(
        'author:first_name',
        [
            ['field' => 'id', 'ascending' => true],
        ]
    ))->toThrow(ForestException::class, '🌳🌳🌳 Cannot replace sort on relation');
});

test('list() should return the records of the childCollection list method when there is no field emulated', function () {
    [$datasource, $collection, $caller, $records] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);
    $list = $sortCollection->list($caller, new PaginatedFilter(sort: new Sort([['field' => 'title', 'ascending' => true]])), new Projection(['id', 'title']));

    expect($list)->toEqual($records);
});

test('list() should return the records sorted when a replace field is provided and the ascending is true', function () {
    [$datasource, $collection, $caller, $records] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);

    $sortCollection->replaceFieldSorting('title', [['field' => 'id', 'ascending' => true]]);
    $list = $sortCollection->list($caller, new PaginatedFilter(sort: new Sort([['field' => 'title', 'ascending' => true]])), new Projection(['id', 'title']));

    expect($list)->toEqual($records);
});

test('list() should return the records sorted when a replace field is provided and the ascending is false', function () {
    [$datasource, $collection, $caller, $records] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);

    $sortCollection->replaceFieldSorting('title', [['field' => 'id', 'ascending' => true]]);
    $list = $sortCollection->list($caller, new PaginatedFilter(sort: new Sort([['field' => 'title', 'ascending' => false]])), new Projection(['id', 'title']));

    expect($list)->toEqual($records);
});

test('list() should return the records sorted when the list doesn\'t have emulated field', function () {
    $records = [
        [
            [
                'id' => 3,
            ],
            [
                'id' => 1,
            ],
            [
                'id' => 2,
            ],
        ],
        [
            [
                'id'     => 2,
                'author' => ['id' => 2, 'firstName' => 'Edward O.', 'lastName' => 'Thorp'],
                'title'  => 'Beat the dealer',
            ],
            [
                'id'     => 1,
                'author' => ['id' => 1, 'firstName' => 'Isaac', 'lastName' => 'Asimov'],
                'title'  => 'Foundation',
            ],
            [
                'id'     => 3,
                'author' => ['id' => 3, 'firstName' => 'Roberto', 'lastName' => 'Saviano'],
                'title'  => 'Gomorrah',
            ],
        ],
    ];

    [$datasourceDecorator, $caller] = factorySortChildCollection($records);
    $collection = $datasourceDecorator->getCollection('Book');
    $collection->emulateFieldSorting('id');
    $list = $collection->list(
        $caller,
        new PaginatedFilter(sort: new Sort([['field' => 'id', 'ascending' => true]]), page: new Page(0, 3)),
        new Projection(['id', 'title'])
    );

    expect($list)->toEqual([
        [
            'id'    => 1,
            'title' => 'Foundation',
        ],
        [
            'id'    => 2,
            'title' => 'Beat the dealer',
        ],
        [
            'id'    => 3,
            'title' => 'Gomorrah',
        ],
    ]);
});

test('list() on relationship should return the records sorted when the list doesn\'t have emulated field', function () {
    $records = [
        [
            [
                'id'     => 3,
                'author' => ['id' => 3],
            ],
            [
                'id'     => 1,
                'author' => ['id' => 1],
            ],
            [
                'id'     => 2,
                'author' => ['id' => 2],
            ],
        ],
        [
            [
                'id'     => 2,
                'author' => ['id' => 2, 'firstName' => 'Edward O.', 'lastName' => 'Thorp'],
                'title'  => 'Beat the dealer',
            ],
            [
                'id'     => 1,
                'author' => ['id' => 1, 'firstName' => 'Isaac', 'lastName' => 'Asimov'],
                'title'  => 'Foundation',
            ],
            [
                'id'     => 3,
                'author' => ['id' => 3, 'firstName' => 'Roberto', 'lastName' => 'Saviano'],
                'title'  => 'Gomorrah',
            ],
        ],
    ];

    [$datasourceDecorator, $caller] = factorySortChildCollection($records);
    $authorCollection = $datasourceDecorator->getCollection('User');
    $authorCollection->emulateFieldSorting('id');

    $collection = $datasourceDecorator->getCollection('Book');

    $list = $collection->list(
        $caller,
        new PaginatedFilter(sort: new Sort([['field' => 'author:id', 'ascending' => true]]), page: new Page(0, 3)),
        new Projection(['id', 'title'])
    );

    expect($list)->toEqual([
        [
            'id'    => 1,
            'title' => 'Foundation',
        ],
        [
            'id'    => 2,
            'title' => 'Beat the dealer',
        ],
        [
            'id'    => 3,
            'title' => 'Gomorrah',
        ],
    ]);
});
