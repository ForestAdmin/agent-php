<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Sort\SortCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;

function factorySortCollection()
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL], isPrimaryKey: true),
            'year'   => new ColumnSchema(columnType: PrimitiveType::STRING, isSortable: true),
            'title'  => new ColumnSchema(columnType: PrimitiveType::STRING, isSortable: false),
            'author' => new ManyToOneSchema(
                foreignKey: 'author_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
            'reviews'     => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                throughTable: 'book_review',
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
                throughCollection: 'BookReview',
            ),
        ]
    );
    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $collectionBookReview = new Collection($datasource, 'BookReview');
    $collectionBookReview->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL], isPrimaryKey: true),
        ]
    );

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionBookReview);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'schemaPath'   => sys_get_temp_dir() . '/.forestadmin-schema.json',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
        'agentUrl'     => 'http://localhost/',
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    $records = [
        [
            'id'     => 1,
            //'authorId' => 1,
            'author' => ['id' => 1, 'firstName' => 'Isaac', 'lastName' => 'Asimov'],
            'title'  => 'Foundation',
        ],
        [
            'id'     => 2,
            //'authorId' => 2,
            'author' => ['id' => 2, 'firstName' => 'Edward O.', 'lastName' => 'Thorp'],
            'title'  => 'Beat the dealer',
        ],
        [
            'id'     => 3,
            //'authorId' => 3,
            'author' => ['id' => 3, 'firstName' => 'Roberto', 'lastName' => 'Saviano'],
            'title'  => 'Gomorrah',
        ],
    ];

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

    return [$datasource, $collectionBook, $caller];
}

test('emulateFieldSorting() should return the field sortable to false', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);
    $sortCollection->emulateFieldSorting('year');

    expect($sortCollection->getFields()['year']->isSortable())->toBeFalse();
});

test('emulateFieldSorting() should throw when the field doesn\'t exist', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);

    expect(fn () => $sortCollection->emulateFieldSorting('afield'))->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Book.afield');
});

test('replaceFieldSorting() should return the field sortable to false', function () {
    [$datasource, $collection] = factorySortCollection();
    $sortCollection = new SortCollection($collection, $datasource);
    $sortCollection->replaceFieldSorting('year', null);

    expect($sortCollection->getFields()['year']->isSortable())->toBeFalse();
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
