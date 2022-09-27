<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Str;

function factory($args = []): Charts
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = $args['bearer'] ?? BEARER;

    $collectionBooks = new Collection($datasource, 'books');
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
                foreignCollection: 'reviews',
                inverseRelationName: 'books',
            ),
            'bookReviews' => new OneToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'reviews',
                inverseRelationName: 'bookReviews',
            ),
        ]
    );

    $collectionReviews = new Collection($datasource, 'reviews');
    $collectionReviews->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'author' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'book'   => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'books',
                inverseRelationName: 'bookReviews',
            ),
        ]
    );

    if (isset($args['books']['results'])) {
        $collectionBooks = mock($collectionBooks)
            ->shouldReceive('aggregate');

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

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasources([$datasource]);

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
        ->andReturnNull()
        ->getMock();

    invokeProperty($chart, 'request', $request);

    return $chart;
}

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
    $chart = factory(
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
                'collection'      => 'books',
                'aggregate_field' => 'price',
                'aggregate'       => 'Sum',
                'filters'         => null,
                'timezone'        => 'Europe/Paris',
            ],
        ]
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new ValueChart(10),
            ]
        );
});

test('makeValue() with previous filter should return a ValueChart', function () {
    $chart = factory(
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
                'collection'      => 'books',
                'aggregate_field' => 'price',
                'aggregate'       => 'Sum',
                'filters'         => "{\"field\":\"date\",\"operator\":\"yesterday\",\"value\":null}",
                'timezone'        => 'Europe/Paris',
            ],
        ]
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new ValueChart(10, 5),
            ]
        );
});

test('makeObjective() should return a ObjectiveChart', function () {
    $chart = factory(
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
                'collection'      => 'books',
                'aggregate_field' => 'price',
                'aggregate'       => 'Count',
                'filters'         => null,
                'timezone'        => 'Europe/Paris',
            ],
        ],
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new ObjectiveChart(10),
            ]
        );
});

test('makePie() should return a PieChart', function () {
    $chart = factory(
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
                'collection'     => 'books',
                'group_by_field' => 'year',
                'aggregate'      => 'Count',
                'timezone'       => 'Europe/Paris',
            ],
        ],
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
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

test('makeLine() with day filter should return a LineChart', function () {
    $chart = factory(
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
                'collection'          => 'books',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Day',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
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
    $chart = factory(
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
                'collection'          => 'books',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Week',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
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

test('makeLine() with month filter should return a LineChart', function () {
    $chart = factory(
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
                'collection'          => 'books',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Month',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
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
    $chart = factory(
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
                'collection'          => 'books',
                'group_by_date_field' => 'date',
                'aggregate'           => 'Count',
                'time_range'          => 'Year',
                'timezone'            => 'Europe/Paris',
            ],
        ]
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
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
    $chart = factory(
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
                'collection'         => 'books',
                'label_field'        => 'title',
                'aggregate'          => 'Count',
                'relationship_field' => 'bookReviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
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
    $chart = factory(
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
                'collection'         => 'books',
                'label_field'        => 'title',
                'aggregate'          => 'Count',
                'relationship_field' => 'reviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );

    expect($chart->handleRequest(['collectionName' => 'books']))
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
    $chart = factory(
        [
            'books'   => [
                'results' => [],
            ],
            'payload' => [
                'type'               => 'Leaderboard',
                'aggregate'          => 'Count',
                'collection'         => 'books',
                'relationship_field' => 'reviews',
                'timezone'           => 'Europe/Paris',
            ],
        ],
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'books']))->toThrow(ForestException::class, '🌳🌳🌳 Failed to generate leaderboard chart: parameters do not match pre-requisites');
});

test('mapArrayToKeyValueAggregate() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new Charts();

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});

