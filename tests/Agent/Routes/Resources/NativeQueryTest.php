<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\NativeQuery;
use ForestAdmin\AgentPHP\Agent\Utils\ArrayHelper;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
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

    if (isset($args['books']['results'])) {
        //        $collectionBooks = \Mockery::mock($collectionBooks)
        //            ->shouldReceive('aggregate')
        //            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null);
        //
        //
        //        if (isset($args['books']['previous'])) {
        //            $collectionBooks = $collectionBooks->andReturn($args['books']['results'][0], $args['books']['results'][1])
        //                ->getMock();
        //        } else {
        //            $collectionBooks = $collectionBooks->andReturn($args['books']['results'])
        //                ->getMock();
        //        }
    }

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionReviews);
    $datasource->addCollection($collectionBookReview);
    $testCase->buildAgent($datasource);

    $_POST = $args['payload'];
    $_GET = ['timezone' => 'Europe/Paris'];

    $attributes = $_POST;
    // unset($attributes['timezone'], $attributes['collection'], $attributes['contextVariables']);
    $attributes = array_filter($attributes, static fn ($value) => ! is_null($value) && $value !== '');
    ArrayHelper::ksortRecursive($attributes);

    $request = Request::createFromGlobals();

    Cache::put(
        'forest.stats',
        [
            0 => $_GET['type'] . ':' . sha1(json_encode($attributes, JSON_THROW_ON_ERROR)),
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

// TODO injectContextVariables

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
        ->toHaveKey('value', (new ObjectiveChart(10, 20))->serialize())
        ->and($result['content']['data']['id']);
});
