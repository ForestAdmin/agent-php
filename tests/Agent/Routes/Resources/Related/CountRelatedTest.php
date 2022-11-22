<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\CountRelated;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;

function factoryCountRelated($args = []): CountRelated
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
            'cars'       => new OneToManySchema(
                originKey: 'user_id',
                originKeyTarget: 'id',
                foreignCollection: 'Car',
                inverseRelationName: 'user',
            ),
        ]
    );

    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'model'   => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand'   => new ColumnSchema(columnType: PrimitiveType::STRING),
            'user_id' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'user'    => new ManyToOneSchema(
                foreignKey: 'user_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
                inverseRelationName: 'cars'
            ),
        ]
    );

    if (isset($args['count'])) {
        $collectionCar = mock($collectionCar)
            ->shouldReceive('aggregate')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null, null)
            ->andReturn(count($args['count']))
            ->getMock();
    }

    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionCar);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'authSecret'   => AUTH_SECRET,
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

    $CountRelated = mock(CountRelated::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($CountRelated, 'request', $request);

    return $CountRelated;
}

test('make() should return a new instance of CountRelated with routes', function () {
    $count = CountRelated::make();

    expect($count)->toBeInstanceOf(CountRelated::class)
        ->and($count->getRoutes())->toHaveKey('forest.related.count');
});

test('handleRequest() should return a response 200', function () {
    $data = [
        [
            'id'      => 1,
            'model'   => 'F8',
            'brand'   => 'Ferrari',
            'user_id' => 1,
        ],
        [
            'id'      => 2,
            'model'   => 'Aventador',
            'brand'   => 'Lamborghini',
            'user_id' => 1,
        ],
    ];

    $count = factoryCountRelated(['count' => $data]);

    expect($count->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'cars']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => [
                    'count' => count($data),
                ],
            ]
        );
});
