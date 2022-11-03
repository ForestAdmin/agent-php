<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Count;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

function factoryCount($args = []): Count
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'Europe/Paris';

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

    $datasource->addCollection($collectionUser);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => AUTH_SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

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
