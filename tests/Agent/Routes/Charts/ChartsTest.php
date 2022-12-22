<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use Illuminate\Support\Str;

function factoryChart($args = []): Charts
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;

    $collectionBooks = new Collection($datasource, 'Book');
    $collectionBooks->addFields(
        [
            'id'          => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'       => new ColumnSchema(columnType: PrimitiveType::STRING),
            'price'       => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'date'        => new ColumnSchema(columnType: PrimitiveType::DATE, filterOperators: [Operators::YESTERDAY]),
            'year'        => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'reviews'     => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                throughTable: 'book_review',
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
                throughCollection: 'BookReview',
            ),
            'bookReviews' => new OneToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'Review',
            ),
        ]
    );

    $collectionBookReview = new Collection($datasource, 'BookReview');
    $collectionBookReview->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'reviews' => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                throughTable: 'book_review',
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
                throughCollection: 'BookReview',
            ),
            'book'    => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
            'review'  => new ManyToOneSchema(
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
            ),
        ]
    );

    $collectionReviews = new Collection($datasource, 'Review');
    $collectionReviews->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'author' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'book'   => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
        ]
    );

    if (isset($args['books']['results'])) {
        $collectionBooks = mock($collectionBooks)
            ->shouldReceive('aggregate')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null, \Mockery::type('string'));
        if (isset($args['books']['previous'])) {
            $collectionBooks = $collectionBooks->andReturn($args['books']['results'][0], $args['books']['results'][1])
                ->getMock();
        } else {
            $collectionBooks = $collectionBooks->andReturn($args['books']['results'])
                ->getMock();
        }
    }

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionReviews);
    $datasource->addCollection($collectionBookReview);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
    ];
    (new Agentfactory($options, []))->addDatasource($datasource)->build();

    $_GET = $args['payload'];
    $request = Request::createFromGlobals();
    $permissions = new Permissions(QueryStringParser::parseCaller($request));

    Cache::put(
        $permissions->getCacheKey(10),
        collect(
            [
                'scopes' => collect(),
                'charts' => collect(
                    [
                        strtolower(Str::plural($_GET['type'])) . ':' . sha1(json_encode(ksort($_GET), JSON_THROW_ON_ERROR)),
                    ]
                ),
            ]
        ),
        300
    );

    $chart = mock(Charts::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($chart, 'request', $request);

    return $chart;
}

test('make() should return a new instance of Chart with routes', function () {
    $chart = Charts::make();

    expect($chart)->toBeInstanceOf(Charts::class)
        ->and($chart->getRoutes())->toHaveKey('forest.chart');
});

test('setType() should return type string', function () {
    $chart = new Charts();
    $chart->setType('Value');

    expect($chart->getType())->toEqual('Value');
});

test('setType() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new Charts();

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});

test('makeValue() should return a ValueChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'sum' => 10,
                    ],
                ],
            ],
            'payload' => [
                'type'            => 'Value',
                'collection'      => 'Book',
                'aggregate_field' => 'price',
                'aggregate'       => 'Sum',
                'filters'         => null,
                'timezone'        => 'Europe/Paris',
            ],
        ]
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new ValueChart(10))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeValue() with previous filter should return a ValueChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results'  => [
                    [['sum' => 10]],
                    [['previous' => 5]],
                ],
                'previous' => true,
            ],
            'payload' => [
                'type'            => 'Value',
                'collection'      => 'Book',
                'aggregate_field' => 'price',
                'aggregate'       => 'Sum',
                'filters'         => "{\"field\":\"date\",\"operator\":\"yesterday\",\"value\":null}",
                'timezone'        => 'Europe/Paris',
            ],
        ]
    );

    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new ValueChart(10, 5))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeObjective() should return a ObjectiveChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'count' => 10,
                    ],
                ],
            ],
            'payload' => [
                'type'            => 'Objective',
                'collection'      => 'Book',
                'aggregate_field' => 'price',
                'aggregate'       => 'Count',
                'filters'         => null,
                'timezone'        => 'Europe/Paris',
            ],
        ],
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new ObjectiveChart(10))->serialize())
        ->and($result['content']['data']['id']);
});

test('makePie() should return a PieChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'year'  => 2021,
                        'count' => 100,
                    ],
                    [
                        'year'  => 2022,
                        'count' => 150,
                    ],
                ],
            ],
            'payload' => [
                'type'           => 'Pie',
                'collection'     => 'Book',
                'group_by_field' => 'year',
                'aggregate'      => 'Count',
                'timezone'       => 'Europe/Paris',
            ],
        ],
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new PieChart([
            [
                'key'   => 2021,
                'value' => 100,
            ],
            [
                'key'   => 2022,
                'value' => 150,
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLine() with day filter should return a LineChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    '03/01/2022' => 10,
                    '10/01/2022' => 15,
                ],
            ],
            'payload' => [
                'type'                => 'Line',
                'collection'          => 'Book',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Day',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new LineChart([
            [
                'label'  => '03/01/2022',
                'values' => ['value' => 10],
            ],
            [
                'label'  => '10/01/2022',
                'values' => ['value' => 15],
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLine() with week filter should return a LineChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    'W01-2022' => 10,
                    'W02-2022' => 15,
                ],
            ],
            'payload' => [
                'type'                => 'Line',
                'collection'          => 'Book',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Week',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new LineChart([
            [
                'label'  => 'W01-2022',
                'values' => ['value' => 10],
            ],
            [
                'label'  => 'W02-2022',
                'values' => ['value' => 15],
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLine() with month filter should return a LineChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    'Jan 2022' => 10,
                    'Feb 2022' => 15,
                ],
            ],
            'payload' => [
                'type'                => 'Line',
                'collection'          => 'Book',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Month',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new LineChart([
            [
                'label'  => 'Jan 2022',
                'values' => ['value' => 10],
            ],
            [
                'label'  => 'Feb 2022',
                'values' => ['value' => 15],
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLine() with month year should return a LineChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    '2022' => 10,
                    '2023' => 15,
                ],
            ],
            'payload' => [
                'type'                => 'Line',
                'collection'          => 'Book',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Year',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new LineChart([
            [
                'label'  => '2022',
                'values' => ['value' => 10],
            ],
            [
                'label'  => '2023',
                'values' => ['value' => 15],
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLeaderboard() should return a LeaderboardChart on a OneToMany Relation', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'title' => 'Foundation',
                        'count' => 15,
                    ],
                    [
                        'title' => 'Harry Potter',
                        'count' => 20,
                    ],
                ],
            ],
            'payload' => [
                'type'               => 'Leaderboard',
                'collection'         => 'Book',
                'label_field'        => 'title',
                'aggregate'          => 'Count',
                'relationship_field' => 'bookReviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new LeaderboardChart([
            [
                'key'   => 'Foundation',
                'value' => 15,
            ],
            [
                'key'   => 'Harry Potter',
                'value' => 20,
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLeaderboard() should return a LeaderboardChart on a ManyToMany Relation', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'title' => 'Foundation',
                        'count' => 15,
                    ],
                    [
                        'title' => 'Harry Potter',
                        'count' => 20,
                    ],
                ],
            ],
            'payload' => [
                'type'               => 'Leaderboard',
                'collection'         => 'Book',
                'label_field'        => 'title',
                'aggregate'          => 'Count',
                'relationship_field' => 'reviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('content')
        ->and($result['content'])
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['content']['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['content']['data']['attributes'])
        ->toHaveKey('value', (new LeaderboardChart([
            [
                'key'   => 'Foundation',
                'value' => 15,
            ],
            [
                'key'   => 'Harry Potter',
                'value' => 20,
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLeaderboard() should throw a ForestException when the request is not filled correctly', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [],
            ],
            'payload' => [
                'type'               => 'Leaderboard',
                'aggregate'          => 'Count',
                'collection'         => 'Book',
                'relationship_field' => 'reviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))->toThrow(ForestException::class, '🌳🌳🌳 Failed to generate leaderboard chart: parameters do not match pre-requisites');
});

test('mapArrayToKeyValueAggregate() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new Charts();

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});
