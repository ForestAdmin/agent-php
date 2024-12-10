<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\NativeQuery;
use ForestAdmin\AgentPHP\Agent\Utils\ArrayHelper;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

function factoryNativeQuery(TestCase $testCase, $args = []): NativeQuery
{
    $datasource = \Mockery::mock(new Datasource())
        ->makePartial()
        ->shouldReceive('getLiveQueryConnections')
        ->andReturn(['EloquentDatasource' => 'sqlite'])
        ->shouldReceive('executeNativeQuery')
        ->andReturn($args['books']['results'])
        ->getMock();

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

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionReviews);
    $datasource->addCollection($collectionBookReview);
    $testCase->buildAgent($datasource);

    $_POST = $args['payload'];
    $_GET = ['timezone' => 'Europe/Paris'];

    $attributes = $_POST;
    $attributes = array_filter($attributes, static fn ($value) => ! is_null($value) && $value !== '');
    ArrayHelper::ksortRecursive($attributes);

    $request = Request::createFromGlobals();

    Cache::put(
        'forest.stats',
        [
            0 => $_POST['type'] . ':' . sha1(json_encode($attributes, JSON_THROW_ON_ERROR)),
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
                'browse' => [
                    0 => 1,
                ],
                'export' => [
                    0 => 1,
                ],
            ],
        ],
        config('permissionExpiration')
    );

    Cache::put(
        'forest.rendering',
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

    $nativeQuery = \Mockery::mock(NativeQuery::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    $testCase->invokeProperty($nativeQuery, 'request', $request);

    return $nativeQuery;
}

test('make() should return a new instance of NativeQuery with routes', function () {
    $chart = NativeQuery::make();

    expect($chart)->toBeInstanceOf(NativeQuery::class)
        ->and($chart->getRoutes())->toHaveKey('forest.native_query');
});

test('setType() should return type string', function () {
    $chart = new NativeQuery();
    $chart->setType('Value');

    expect($chart->getType())->toEqual('Value');
});

test('setType() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new NativeQuery();

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});

test('handleRequest() should throw a ForestException when the connection attribute is missing', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [],
            'payload' => [
                'type'             => 'Value',
                'query'            => 'SELECT count(*) as value FROM books WHERE id > 1;',
                'contextVariables' => [],
            ],
        ]
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))
        ->toThrow(ForestException::class, '🌳🌳🌳 Missing native query connection attribute');
});

test('handleRequest() should throw a ForestException when the connectionName is unknown', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [],
            'payload' => [
                'type'             => 'Value',
                'query'            => 'SELECT count(*) as value FROM books WHERE id > 1;',
                'contextVariables' => [],
                'connectionName'   => 'FOO',
            ],
        ]
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))
        ->toThrow(ForestException::class, "🌳🌳🌳 No datasource found for connection: FOO");
});

test('makeValue() should return a ValueChart', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'value' => 10,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Value',
                'query'            => 'SELECT count(*) as value FROM books WHERE id > 1',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ]
    );

    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result['content']['data']['attributes'])->toEqual([
        'value' => (new ValueChart(10))->serialize(),
    ]);
});

test('makeValue() throw an exception when the key value is not present in the result', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'key' => 10,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Value',
                'query'            => 'SELECT count(*) as value FROM books WHERE id > 1',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ]
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))
        ->toThrow(ForestException::class, "🌳🌳🌳 The key 'value' is not present in the result");
});

test('makeObjective() should return a ObjectiveChart', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'value'     => 10,
                        'objective' => 20,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Objective',
                'query'            => 'SELECT COUNT(*) AS value, 200 AS objective FROM books',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ],
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result['content']['data']['attributes'])
        ->toHaveKey('value', (new ObjectiveChart(10, 20))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeObjective throw an exception when the keys value and objective are not present in the result', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'value' => 10,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Objective',
                'query'            => 'SELECT COUNT(*) AS value FROM books',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ],
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))
        ->toThrow(ForestException::class, "🌳🌳🌳 The keys 'value' and 'objective' are not present in the result");
});

test('makePie() should return a PieChart', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'key'   => 1,
                        'value' => 100,
                    ],
                    [
                        'key'   => 2,
                        'value' => 150,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Pie',
                'query'            => 'SELECT posts.user_id AS key, COUNT(*) AS value FROM books GROUP BY user_id',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ],
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result['content']['data']['attributes'])
        ->toHaveKey('value', (new PieChart([
            [
                'key'   => 1,
                'value' => 100,
            ],
            [
                'key'   => 2,
                'value' => 150,
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makePie() throw an exception when the keys key and value are not present in the result', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'key' => 1,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Pie',
                'query'            => 'SELECT posts.user_id AS key FROM books GROUP BY user_id',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ],
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))
        ->toThrow(ForestException::class, "🌳🌳🌳 The keys 'key' and 'value' are not present in the result");
});

test('makeLine() with should return a LineChart', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'key'    => '2024-01-01',
                        'value'  => 10,
                    ],
                    [
                        'key'    => '2024-02-01',
                        'value'  => 20,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Line',
                'query'            => 'SELECT DATE_TRUNC(\'month\', published_at) AS key, COUNT(*) as value FROM books GROUP BY key ORDER BY key;',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ]
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result['content']['data']['attributes'])
        ->toHaveKey('value', (new LineChart([
            [
                'label'  => '2024-01-01',
                'values' => ['value' => 10],
            ],
            [
                'label'  => '2024-02-01',
                'values' => ['value' => 20],
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLine() should throw an exception when the keys label and values are not present in the result', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'label' => '2024-01-01',
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Line',
                'query'            => 'SELECT DATE_TRUNC(\'month\', published_at) AS key FROM books GROUP BY key ORDER BY key;',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ]
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))
        ->toThrow(ForestException::class, "🌳🌳🌳 The keys 'key' and 'value' are not present in the result");
});

test('makeLeaderboard() should return a LeaderboardChart on a OneToMany Relation', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'key'   => 1,
                        'value' => 10,
                    ],
                    [
                        'key'   => 2,
                        'value' => 11,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Leaderboard',
                'query'            => 'SELECT tags.id AS key, SUM(posts.id) AS value FROM books JOIN tags ON posts.tag_id = tags.id GROUP BY key ORDER BY value DESC LIMIT 10;',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ],
    );
    $result = $chart->handleRequest(['collectionName' => 'Book']);

    expect($result['content']['data']['attributes'])
        ->toHaveKey('value', (new LeaderboardChart([
            [
                'key'   => 1,
                'value' => 10,
            ],
            [
                'key'   => 2,
                'value' => 11,
            ],
        ]))->serialize())
        ->and($result['content']['data']['id']);
});

test('makeLeaderboard() should throw an exception when the keys key and value are not present in the result', function () {
    $chart = factoryNativeQuery(
        $this,
        [
            'books'   => [
                'results' => [
                    [
                        'key' => 1,
                    ],
                ],
            ],
            'payload' => [
                'type'             => 'Leaderboard',
                'query'            => 'SELECT tags.id AS key FROM books JOIN tags ON posts.tag_id = tags.id GROUP BY key ORDER BY value DESC LIMIT 10;',
                'contextVariables' => [],
                'connectionName'   => 'EloquentDatasource',
            ],
        ],
    );

    expect(fn () => $chart->handleRequest(['collectionName' => 'Book']))
        ->toThrow(ForestException::class, "🌳🌳🌳 The keys 'key' and 'value' are not present in the result");
});
