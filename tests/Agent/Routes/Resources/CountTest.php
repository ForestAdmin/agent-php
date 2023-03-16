<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Count;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\SchemaCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factoryCount($args = []): Count
{
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    if (isset($args['count'])) {
        $collectionUser = mock($collectionUser)
            ->shouldReceive('aggregate')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class))
            ->andReturn(count($args['count']))
            ->getMock();
    }

    if (isset($args['countDisable'])) {
        $collectionUser = mock($collectionUser)
            ->shouldReceive('isCountable')
            ->andReturnFalse()
            ->getMock();

        $schemaCollection = new SchemaCollection($collectionUser, $datasource);
        $schemaCollection->overrideSchema('countable', false);
    }

    $datasource->addCollection($collectionUser);
    buildAgent($datasource);

    $request = Request::createFromGlobals();
    $permissions = new Permissions(QueryStringParser::parseCaller($request));

    Cache::put(
        $permissions->getCacheKey(10),
        collect(
            [
                'actions' => collect(
                    [
                        'browse:User' => collect([1]),
                    ]
                ),
                'scopes'  => collect(),
            ]
        ),
        300
    );

    $count = mock(Count::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($count, 'request', $request);

    return $count;
}

test('make() should return a new instance of Count with routes', function () {
    $count = Count::make();

    expect($count)->toBeInstanceOf(Count::class)
        ->and($count->getRoutes())->toHaveKey('forest.count');
});

test('handleRequest() should return a response 200', function () {
    $data = [
        [
            'id'         => 1,
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ],
        [
            'id'         => 2,
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
        ],
    ];

    $count = factoryCount(['count' => $data]);

    expect($count->handleRequest(['collectionName' => 'User']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => [
                    'count' => count($data),
                ],
            ]
        );
});

test('handleRequest() should return deactivate count when the collecion is not countable', function () {
    $count = factoryCount(['countDisable' => true]);

    expect($count->handleRequest(['collectionName' => 'User']))->toEqual([
        'content' => [
            'meta' => [
                'count' => 'deactivated',
            ],
        ],
    ]);
});
