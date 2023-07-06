<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Utils\ArrayHelper;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;

use function ForestAdmin\config;

function factoryChart($args = []): Charts
{
    $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'Book');
    $collectionBooks->addFields(
        [
            'id'          => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'       => new ColumnSchema(columnType: PrimitiveType::STRING),
            'price'       => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'date'        => new ColumnSchema(columnType: PrimitiveType::DATE, filterOperators: [Operators::YESTERDAY]),
            'year'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL]),
            'reviews'     => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
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
            ->shouldReceive('aggregate');

        if (isset($args['type']) && $args['type'] === 'leaderboard') {
            $collectionBooks->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null);
        } else {
            $collectionBooks->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class));
        }

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
    buildAgent($datasource);

    $_GET = $args['payload'];

    $attributes = $_GET;
    unset($attributes['timezone'], $attributes['collection'], $attributes['contextVariables']);
    $attributes = array_filter($attributes, static fn ($value) => ! is_null($value) && $value !== '');
    ArrayHelper::ksortRecursive($attributes);

    $request = Request::createFromGlobals();

    Cache::put(
        'forest.stats',
        [
            0 => $_GET['type']. ':' . sha1(json_encode($attributes, JSON_THROW_ON_ERROR)),
        ],
        config('permissionExpiration')
    );

    Cache::put(
        'forest.users',
        [
            1 => [
                'id'              => 1,
                'firstName'       => 'John',
                'lastName'        => 'Doe',
                'fullName'        => 'John Doe',
                'email'           => 'john.doe@domain.com',
                'tags'            => [],
                'roleId'          => 1,
                'permissionLevel' => 'admin',
            ],
        ],
        config('permissionExpiration')
    );

    Cache::put(
        'forest.collections',
        [
            'User' => [
                'browse'  => [
                    0 => 1,
                ],
                'export'  => [
                    0 => 1,
                ],
            ],
        ],
        config('permissionExpiration')
    );

    Cache::put(
        'forest.scopes',
        collect(
            [
                'scopes' => collect([]),
                'team'   => [
                    'id'   => 44,
                    'name' => 'Operations',
                ],
            ]
        ),
        config('permissionExpiration')
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


test('injectContextVariables() should update the request', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'value' => 10,
                    ],
                ],
            ],
            'payload' => [
                'aggregateFieldName'   => 'price',
                'aggregator'           => 'Sum',
                'sourceCollectionName' => 'Book',
                'type'                 => 'Value',
                'timezone'             => 'Europe/Paris',
                'filter'               => ["aggregator" => "and", "conditions" => [["operator" => "equal", "value" => "{{dropdown1.selectedValue}}", "field" => "year"]]],
                'contextVariables'     => ['dropdown1.selectedValue' => 2022],
            ],
        ]
    );
    $chart->handleRequest(['collectionName' => 'Book']);
    /** @var Filter $filter */
    $filter = invokeProperty($chart, 'filter');

    expect($filter->getConditionTree())
        ->toBeInstanceOf(ConditionTreeLeaf::class)
        ->and($filter->getConditionTree())
        ->toEqual(new ConditionTreeLeaf('year', Operators::EQUAL, '2022'));
});

test('makeValue() should return a ValueChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'value' => 10,
                    ],
                ],
            ],
            'payload' => [
                'aggregateFieldName'   => 'price',
                'aggregator'           => 'Sum',
                'sourceCollectionName' => 'Book',
                'type'                 => 'Value',
                'timezone'             => 'Europe/Paris',
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
                    [['value' => 10]],
                    [['value' => 5]],
                ],
                'previous' => true,
            ],
            'payload' => [
                'type'                 => 'Value',
                'sourceCollectionName' => 'Book',
                'aggregateFieldName'   => 'price',
                'aggregator'           => 'Sum',
                'filter'               => "{\"field\":\"date\",\"operator\":\"yesterday\",\"value\":null}",
                'timezone'             => 'Europe/Paris',
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
                        'value' => 10,
                    ],
                ],
            ],
            'payload' => [
                'type'                 => 'Objective',
                'sourceCollectionName' => 'Book',
                'aggregateFieldName'   => 'price',
                'aggregator'           => 'Count',
                'filter'               => null,
                'timezone'             => 'Europe/Paris',
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
                        'group' => ['year' => 2021],
                        'value' => 100,
                    ],
                    [
                        'group' => ['year' => 2022],
                        'value' => 150,
                    ],
                ],
            ],
            'payload' => [
                'type'                 => 'Pie',
                'sourceCollectionName' => 'Book',
                'groupByFieldName'     => 'year',
                'aggregator'           => 'Count',
                'timezone'             => 'Europe/Paris',
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
                    [
                        'value' => 10,
                        'group' => ['date' => '2022-01-03 00:00:00'],
                    ],
                    [
                        'value' => 15,
                        'group' => ['date' => '2022-01-10 00:00:00'],
                    ],
                ],
            ],
            'payload' => [
                'type'                 => 'Line',
                'sourceCollectionName' => 'Book',
                'groupByFieldName'     => 'date',
                'aggregator'           => 'Count',
                'timeRange'            => 'Day',
                'timezone'             => 'Europe/Paris',
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
                    [
                        'value' => 10,
                        'group' => ['date' => '2022-01-03 00:00:00'],
                    ],
                    [
                        'value' => 15,
                        'group' => ['date' => '2022-01-10 00:00:00'],
                    ],
                ],
            ],
            'payload' => [
                'type'                 => 'Line',
                'sourceCollectionName' => 'Book',
                'groupByFieldName'     => 'date',
                'aggregator'           => 'Count',
                'timeRange'            => 'Week',
                'timezone'             => 'Europe/Paris',
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
                    [
                        'value' => 10,
                        'group' => ['date' => '2022-01-03 00:00:00'],
                    ],
                    [
                        'value' => 15,
                        'group' => ['date' => '2022-02-03 00:00:00'],
                    ],
                ],
            ],
            'payload' => [
                'type'                 => 'Line',
                'sourceCollectionName' => 'Book',
                'groupByFieldName'     => 'date',
                'aggregator'           => 'Count',
                'timeRange'            => 'Month',
                'timezone'             => 'Europe/Paris',
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
                    [
                        'value' => 10,
                        'group' => ['date' => '2022-01-03 00:00:00'],
                    ],
                    [
                        'value' => 15,
                        'group' => ['date' => '2023-01-03 00:00:00'],
                    ],
                ],
            ],
            'payload' => [
                'type'                 => 'Line',
                'sourceCollectionName' => 'Book',
                'groupByFieldName'     => 'date',
                'aggregator'           => 'Count',
                'timeRange'            => 'Year',
                'timezone'             => 'Europe/Paris',
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

test('makeLine() should return an exception when the timeRange is not defined', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'value' => 10,
                        'group' => ['date' => '2022-01-03 00:00:00'],
                    ],
                ],
            ],
            'payload' => [
                'type'                 => 'Line',
                'sourceCollectionName' => 'Book',
                'groupByFieldName'     => 'date',
                'aggregator'           => 'Count',
                'timezone'             => 'Europe/Paris',
            ],
        ]
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))->toThrow(ForestException::class, '🌳🌳🌳 The parameter timeRange is not defined');
});

test('makeLeaderboard() should return a LeaderboardChart on a OneToMany Relation', function () {
    $chart = factoryChart(
        [
            'type'    => 'leaderboard',
            'books'   => [
                'results' => [
                    [
                        'value' => 15,
                        'group' => ['title' => 'Foundation'],
                    ],
                    [
                        'value' => 20,
                        'group' => ['title' => 'Harry Potter'],
                    ],
                ],
            ],
            'payload' => [
                'type'                  => 'Leaderboard',
                'sourceCollectionName'  => 'Book',
                'labelFieldName'        => 'title',
                'aggregator'            => 'Count',
                'relationshipFieldName' => 'bookReviews',
                'timezone'              => 'Europe/Paris',
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
            'type'    => 'leaderboard',
            'books'   => [
                'results' => [
                    [
                        'value' => 15,
                        'group' => ['title' => 'Foundation'],
                    ],
                    [
                        'value' => 20,
                        'group' => ['title' => 'Harry Potter'],
                    ],
                ],
            ],
            'payload' => [
                'type'                  => 'Leaderboard',
                'sourceCollectionName'  => 'Book',
                'labelFieldName'        => 'title',
                'aggregator'            => 'Count',
                'relationshipFieldName' => 'reviews',
                'timezone'              => 'Europe/Paris',
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
                'type'                  => 'Leaderboard',
                'aggregator'            => 'Count',
                'sourceCollectionName'  => 'Book',
                'relationshipFieldName' => 'reviews',
                'timezone'              => 'Europe/Paris',
            ],
        ],
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))->toThrow(ForestException::class, '🌳🌳🌳 Failed to generate leaderboard chart: parameters do not match pre-requisites');
});

test('mapArrayToKeyValueAggregate() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new Charts();

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});
