<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
<<<<<<< HEAD
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
=======
>>>>>>> f2bfb7c (chore: add test on Chart resources)
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
<<<<<<< HEAD
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
=======
>>>>>>> f2bfb7c (chore: add test on Chart resources)
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

function factory($args = []): Datasource
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJqb2huLmRvZUBkb21haW4uY29tIiwiZmlyc3ROYW1lIjoiSm9obiIsImxhc3ROYW1lIjoiRG9lIiwidGVhbSI6IkRldmVsb3BlcnMiLCJyZW5kZXJpbmdJZCI6IjEwIiwidGFncyI6W10sInRpbWV6b25lIjoiRXVyb3BlL1BhcmlzIn0.-zTadg2QjQSH6b5kZxa4kSfBCZBAZq9T4ZJqAkTAQcs';

    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'          => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'       => new ColumnSchema(columnType: PrimitiveType::STRING),
            'price'       => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'date'        => new ColumnSchema(columnType: PrimitiveType::DATE),
<<<<<<< HEAD
            'year'        => new ColumnSchema(columnType: PrimitiveType::NUMBER),
=======
>>>>>>> f2bfb7c (chore: add test on Chart resources)
            'reviews'     => new ManyToManySchema(
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                throughTable: 'bookReview',
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'reviews'
            ),
            'bookReviews' => new OneToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'reviews',
            ),
        ]
    );

    $collectionReviews = new Collection($datasource, 'reviews');
    $collectionReviews->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'author' => new ColumnSchema(columnType: PrimitiveType::STRING),
<<<<<<< HEAD
            'book'   => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'books',
            ),
=======
>>>>>>> f2bfb7c (chore: add test on Chart resources)
        ]
    );

    if (isset($args['books']['results'])) {
        $collectionBooks = mock($collectionBooks)
            ->shouldReceive('aggregate')
            ->andReturn($args['books']['results'])
            ->getMock();
    }

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionReviews);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d',
        'isProduction' => false,
    ];
    (new AgentFactory($options))->addDatasources([$datasource]);

    return $datasource;
}

test('setType() should return type string', function () {
    $chart = new Charts(new ForestAdminHttpDriverServices());
    $chart->setType('Value');

    expect($chart->getType())->toEqual('Value');
});

test('setType() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new Charts(new ForestAdminHttpDriverServices());

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});

test('makeValue() should return a ValueChart', function () {
<<<<<<< HEAD
    factory(
=======
    $datasource = factory(
>>>>>>> f2bfb7c (chore: add test on Chart resources)
        [
            'books' => [
                'results' => [
                    [
                        'sum' => 10,
                    ],
                ],
            ],
        ]
    );

    $_GET = [
        'type'            => 'Value',
        'collection'      => 'books',
        'aggregate_field' => 'price',
        'aggregate'       => 'Sum',
        'filters'         => null,
        'timezone'        => 'Europe/Paris',
    ];

    $chart = new Charts(new ForestAdminHttpDriverServices());

    expect($chart->handleRequest(['collectionName' => 'books']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new ValueChart(10),
            ]
        );
});

<<<<<<< HEAD
test('makeObjective() should return a ObjectiveChart', function () {
    factory(
        [
            'books' => [
                'results' => [
                    [
                        'count' => 10,
                    ],
                ],
            ],
        ]
    );

    $_GET = [
        'type'            => 'Objective',
        'collection'      => 'books',
        'aggregate_field' => 'price',
        'aggregate'       => 'Count',
        'filters'         => null,
        'timezone'        => 'Europe/Paris',
    ];

    $chart = new Charts(new ForestAdminHttpDriverServices());

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
    factory(
        [
            'books' => [
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
        ]
    );

    $_GET = [
        'type'           => 'Pie',
        'collection'     => 'books',
        'group_by_field' => 'year',
        'aggregate'      => 'Count',
        'timezone'       => 'Europe/Paris',
    ];

    $chart = new Charts(new ForestAdminHttpDriverServices());

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

test('makeLine() should return a LineChart', function () {
    factory(
        [
            'books' => [
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
        ]
    );

    $_GET = [
        'type'                => 'Line',
        'collection'          => 'books',
        'group_by_date_field' => 'date',
        'aggregate'           => 'Count',
        'time_range'          => 'Week',
        'timezone'            => 'Europe/Paris',
    ];

    $chart = new Charts(new ForestAdminHttpDriverServices());

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

test('makeLeaderboard() should return a LeaderboardChart on a OneToMany Relation', function () {
    factory(
        [
            'books' => [
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
        ]
    );

    $_GET = [
        'type'               => 'Leaderboard',
        'collection'         => 'books',
        'label_field'        => 'title',
        'aggregate'          => 'Count',
        'relationship_field' => 'bookReviews',
        'timezone'           => 'Europe/Paris',
    ];

    $chart = new Charts(new ForestAdminHttpDriverServices());

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
    factory(
        [
            'books' => [
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
        ]
    );

    $_GET = [
        'type'               => 'Leaderboard',
        'collection'         => 'books',
        'label_field'        => 'title',
        'aggregate'          => 'Count',
        'relationship_field' => 'reviews',
        'timezone'           => 'Europe/Paris',
    ];

    $chart = new Charts(new ForestAdminHttpDriverServices());

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
    factory(
        [
            'books' => [
                'results' => [],
            ],
        ]
    );
    $_GET = [
        'type'               => 'Leaderboard',
        'aggregate'          => 'Count',
        'collection'         => 'books',
        'relationship_field' => 'reviews',
        'timezone'           => 'Europe/Paris',
    ];

    $chart = new Charts(new ForestAdminHttpDriverServices());

    expect(fn () => $chart->handleRequest(['collectionName' => 'books']))->toThrow(ForestException::class, '🌳🌳🌳 Failed to generate leaderboard chart: parameters do not match pre-requisites');
});

test('mapArrayToKeyValueAggregate() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new Charts(new ForestAdminHttpDriverServices());

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});
=======

>>>>>>> f2bfb7c (chore: add test on Chart resources)
