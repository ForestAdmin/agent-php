<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
<<<<<<< HEAD
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
=======
>>>>>>> 662ea58 (chore: update chart test)
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
<<<<<<< HEAD
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
=======
>>>>>>> 662ea58 (chore: update chart test)
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Str;

<<<<<<< HEAD
function factoryChart($args = []): Charts
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;

    $collectionBooks = new Collection($datasource, 'Book');
=======
function factory($args = []): Charts
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = $args['bearer'] ?? BEARER;

    $collectionBooks = new Collection($datasource, 'books');
>>>>>>> 662ea58 (chore: update chart test)
    $collectionBooks->addFields(
        [
            'id'          => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'       => new ColumnSchema(columnType: PrimitiveType::STRING),
            'price'       => new ColumnSchema(columnType: PrimitiveType::NUMBER),
<<<<<<< HEAD
            'date'        => new ColumnSchema(columnType: PrimitiveType::DATE, filterOperators: [Operators::YESTERDAY]),
=======
            'date'        => new ColumnSchema(columnType: PrimitiveType::DATE),
>>>>>>> 662ea58 (chore: update chart test)
            'year'        => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'reviews'     => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                throughTable: 'book_review',
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
<<<<<<< HEAD
                foreignCollection: 'Review',
                inverseRelationName: 'Book',
=======
                foreignCollection: 'reviews',
                inverseRelationName: 'books',
>>>>>>> 662ea58 (chore: update chart test)
            ),
            'bookReviews' => new OneToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
<<<<<<< HEAD
                foreignCollection: 'Review',
=======
                foreignCollection: 'reviews',
>>>>>>> 662ea58 (chore: update chart test)
                inverseRelationName: 'bookReviews',
            ),
        ]
    );

<<<<<<< HEAD
    $collectionReviews = new Collection($datasource, 'Review');
=======
    $collectionReviews = new Collection($datasource, 'reviews');
>>>>>>> 662ea58 (chore: update chart test)
    $collectionReviews->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'author' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'book'   => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
<<<<<<< HEAD
                foreignCollection: 'Book',
=======
                foreignCollection: 'books',
>>>>>>> 662ea58 (chore: update chart test)
                inverseRelationName: 'bookReviews',
            ),
        ]
    );

    if (isset($args['books']['results'])) {
        $collectionBooks = mock($collectionBooks)
            ->shouldReceive('aggregate')
<<<<<<< HEAD
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null, \Mockery::type('string'));

        if (isset($args['books']['previous'])) {
            $collectionBooks = $collectionBooks->andReturn($args['books']['results'][0], $args['books']['results'][1])
                ->getMock();
        } else {
            $collectionBooks = $collectionBooks->andReturn($args['books']['results'])
                ->getMock();
        }
=======
            ->andReturn($args['books']['results'])
            ->getMock();
>>>>>>> 662ea58 (chore: update chart test)
    }

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionReviews);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => SECRET,
        'isProduction' => false,
    ];
<<<<<<< HEAD
    (new Agentfactory($options, []))->addDatasources([$datasource]);
=======
    (new AgentFactory($options, []))->addDatasources([$datasource]);
>>>>>>> 662ea58 (chore: update chart test)

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
<<<<<<< HEAD
=======
        ->andReturnNull()
>>>>>>> 662ea58 (chore: update chart test)
        ->getMock();

    invokeProperty($chart, 'request', $request);

    return $chart;
}

<<<<<<< HEAD
test('make() should return a new instance of Chart with routes', function () {
    $chart = Charts::make();

    expect($chart)->toBeInstanceOf(Charts::class)
        ->and($chart->getRoutes())->toHaveKey('forest.chart');
});


=======
>>>>>>> 662ea58 (chore: update chart test)
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
<<<<<<< HEAD
    $chart = factoryChart(
=======
    $chart = factory(
>>>>>>> 662ea58 (chore: update chart test)
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
<<<<<<< HEAD
                'collection'      => 'Book',
=======
                'collection'      => 'books',
>>>>>>> 662ea58 (chore: update chart test)
                'aggregate_field' => 'price',
                'aggregate'       => 'Sum',
                'filters'         => null,
                'timezone'        => 'Europe/Paris',
            ],
        ]
    );

<<<<<<< HEAD
    expect($chart->handleRequest(['collectionName' => 'Book']))
=======
    expect($chart->handleRequest(['collectionName' => 'books']))
>>>>>>> 662ea58 (chore: update chart test)
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new ValueChart(10),
            ]
        );
});

<<<<<<< HEAD
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

    expect($chart->handleRequest(['collectionName' => 'Book']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new ValueChart(10, 5),
            ]
        );
});

test('makeObjective() should return a ObjectiveChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
=======
test('makeObjective() should return a ObjectiveChart', function () {
    $chart = factory(
        [
            'books' => [
>>>>>>> 662ea58 (chore: update chart test)
                'results' => [
                    [
                        'count' => 10,
                    ],
                ],
            ],
            'payload' => [
                'type'            => 'Objective',
<<<<<<< HEAD
                'collection'      => 'Book',
=======
                'collection'      => 'books',
>>>>>>> 662ea58 (chore: update chart test)
                'aggregate_field' => 'price',
                'aggregate'       => 'Count',
                'filters'         => null,
                'timezone'        => 'Europe/Paris',
            ],
        ],
    );

<<<<<<< HEAD
    expect($chart->handleRequest(['collectionName' => 'Book']))
=======
    expect($chart->handleRequest(['collectionName' => 'books']))
>>>>>>> 662ea58 (chore: update chart test)
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new ObjectiveChart(10),
            ]
        );
});

test('makePie() should return a PieChart', function () {
<<<<<<< HEAD
    $chart = factoryChart(
        [
            'books'   => [
=======
    $chart = factory(
        [
            'books' => [
>>>>>>> 662ea58 (chore: update chart test)
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
<<<<<<< HEAD
                'collection'     => 'Book',
=======
                'collection'     => 'books',
>>>>>>> 662ea58 (chore: update chart test)
                'group_by_field' => 'year',
                'aggregate'      => 'Count',
                'timezone'       => 'Europe/Paris',
            ],
        ],
    );

<<<<<<< HEAD
    expect($chart->handleRequest(['collectionName' => 'Book']))
=======
    expect($chart->handleRequest(['collectionName' => 'books']))
>>>>>>> 662ea58 (chore: update chart test)
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new PieChart([
                    [
                        'key'   => 2021,
                        'value' => 100,
                    ],
                    [
                        'key'   => 2022,
                        'value' => 150,
                    ],
                ]),
            ]
        );
});

<<<<<<< HEAD
test('makeLine() with day filter should return a LineChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
=======
test('makeLine() should return a LineChart', function () {
    $chart = factory(
        [
            'books' => [
>>>>>>> 662ea58 (chore: update chart test)
                'results' => [
                    [
                        'label' => new \DateTime('2022-01-03 00:00:00'),
                        'value' => 10,
                    ],
                    [
                        'label' => new \DateTime('2022-01-10 00:00:00'),
                        'value' => 15,
                    ],
                ],
            ],
            'payload' => [
                'type'                => 'Line',
<<<<<<< HEAD
                'collection'          => 'Book',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Day',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );

    expect($chart->handleRequest(['collectionName' => 'Book']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new LineChart([
                    [
                        'label'  => '03/01/2022',
                        'values' => 10,
                    ],
                    [
                        'label'  => '10/01/2022',
                        'values' => 15,
                    ],
                ]),
            ]
        );
});

test('makeLine() with week filter should return a LineChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'label' => new \DateTime('2022-01-03 00:00:00'),
                        'value' => 10,
                    ],
                    [
                        'label' => new \DateTime('2022-01-10 00:00:00'),
                        'value' => 15,
                    ],
                ],
            ],
            'payload' => [
                'type'                => 'Line',
                'collection'          => 'Book',
=======
                'collection'          => 'books',
>>>>>>> 662ea58 (chore: update chart test)
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Week',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );

<<<<<<< HEAD
    expect($chart->handleRequest(['collectionName' => 'Book']))
=======
    expect($chart->handleRequest(['collectionName' => 'books']))
>>>>>>> 662ea58 (chore: update chart test)
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new LineChart([
                    [
                        'label'  => 'W01-2022',
                        'values' => 10,
                    ],
                    [
                        'label'  => 'W02-2022',
                        'values' => 15,
                    ],
                ]),
            ]
        );
});

<<<<<<< HEAD
test('makeLine() with month filter should return a LineChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'label' => new \DateTime('2022-01-01 00:00:00'),
                        'value' => 10,
                    ],
                    [
                        'label' => new \DateTime('2022-02-01 00:00:00'),
                        'value' => 15,
                    ],
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

    expect($chart->handleRequest(['collectionName' => 'Book']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new LineChart([
                    [
                        'label'  => 'Jan 2022',
                        'values' => 10,
                    ],
                    [
                        'label'  => 'Feb 2022',
                        'values' => 15,
                    ],
                ]),
            ]
        );
});

test('makeLine() with month year should return a LineChart', function () {
    $chart = factoryChart(
        [
            'books'   => [
                'results' => [
                    [
                        'label' => new \DateTime('2022-01-01 00:00:00'),
                        'value' => 10,
                    ],
                    [
                        'label' => new \DateTime('2023-01-01 00:00:00'),
                        'value' => 15,
                    ],
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

    expect($chart->handleRequest(['collectionName' => 'Book']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new LineChart([
                    [
                        'label'  => '2022',
                        'values' => 10,
                    ],
                    [
                        'label'  => '2023',
                        'values' => 15,
                    ],
                ]),
            ]
        );
});

test('makeLeaderboard() should return a LeaderboardChart on a OneToMany Relation', function () {
    $chart = factoryChart(
        [
            'books'   => [
=======
test('makeLeaderboard() should return a LeaderboardChart on a OneToMany Relation', function () {
    $chart = factory(
        [
            'books' => [
>>>>>>> 662ea58 (chore: update chart test)
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
<<<<<<< HEAD
                'collection'         => 'Book',
=======
                'collection'         => 'books',
>>>>>>> 662ea58 (chore: update chart test)
                'label_field'        => 'title',
                'aggregate'          => 'Count',
                'relationship_field' => 'bookReviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );

<<<<<<< HEAD
    expect($chart->handleRequest(['collectionName' => 'Book']))
=======
    expect($chart->handleRequest(['collectionName' => 'books']))
>>>>>>> 662ea58 (chore: update chart test)
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new LeaderboardChart([
                    [
                        'key'   => 'Foundation',
                        'value' => 15,
                    ],
                    [
                        'key'   => 'Harry Potter',
                        'value' => 20,
                    ],
                ]),
            ]
        );
});

test('makeLeaderboard() should return a LeaderboardChart on a ManyToMany Relation', function () {
<<<<<<< HEAD
    $chart = factoryChart(
        [
            'books'   => [
=======
    $chart = factory(
        [
            'books' => [
>>>>>>> 662ea58 (chore: update chart test)
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
<<<<<<< HEAD
                'collection'         => 'Book',
=======
                'collection'         => 'books',
>>>>>>> 662ea58 (chore: update chart test)
                'label_field'        => 'title',
                'aggregate'          => 'Count',
                'relationship_field' => 'reviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );

<<<<<<< HEAD
    expect($chart->handleRequest(['collectionName' => 'Book']))
=======
    expect($chart->handleRequest(['collectionName' => 'books']))
>>>>>>> 662ea58 (chore: update chart test)
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new LeaderboardChart([
                    [
                        'key'   => 'Foundation',
                        'value' => 15,
                    ],
                    [
                        'key'   => 'Harry Potter',
                        'value' => 20,
                    ],
                ]),
            ]
        );
});

test('makeLeaderboard() should throw a ForestException when the request is not filled correctly', function () {
<<<<<<< HEAD
    $chart = factoryChart(
        [
            'books'   => [
=======
    $chart = factory(
        [
            'books' => [
>>>>>>> 662ea58 (chore: update chart test)
                'results' => [],
            ],
            'payload' => [
                'type'               => 'Leaderboard',
                'aggregate'          => 'Count',
<<<<<<< HEAD
                'collection'         => 'Book',
=======
                'collection'         => 'books',
>>>>>>> 662ea58 (chore: update chart test)
                'relationship_field' => 'reviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );

<<<<<<< HEAD
    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))->toThrow(ForestException::class, '🌳🌳🌳 Failed to generate leaderboard chart: parameters do not match pre-requisites');
=======
    expect(fn () => $chart->handleRequest(['collectionName' => 'books']))->toThrow(ForestException::class, '🌳🌳🌳 Failed to generate leaderboard chart: parameters do not match pre-requisites');
>>>>>>> 662ea58 (chore: update chart test)
});

test('mapArrayToKeyValueAggregate() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new Charts();

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});

