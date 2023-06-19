<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\UpdateRelated;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

use function ForestAdmin\config;

function factoryUpdateRelated($args = []): UpdateRelated
{
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'car'        => new OneToOneSchema(
                originKey: 'user_id',
                originKeyTarget: 'id',
                foreignCollection: 'Car',
            ),
        ]
    );

    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'model'   => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand'   => new ColumnSchema(columnType: PrimitiveType::STRING),
            'user_id' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'user'    => new ManyToOneSchema(
                foreignKey: 'user_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
        ]
    );

    if (isset($args['Car']['listing'])) {
        $collectionCar = mock($collectionCar)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type(Projection::class))
            ->andReturn($args['Car']['listing'])
            ->getMock();
    }

    if (isset($args['User']['listing'])) {
        $collectionUser = mock($collectionUser)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type(Projection::class))
            ->andReturn($args['User']['listing'])
            ->getMock();
    }

    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionCar);
    buildAgent($datasource);

    SchemaEmitter::getSerializedSchema($datasource);

    $request = Request::createFromGlobals();

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
                'edit'  => [
                    0 => 1,
                ],
            ],
            'Car'  => [
                'edit'  => [
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

    $update = mock(UpdateRelated::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($update, 'request', $request);

    return $update;
}

test('make() should return a new instance of UpdateRelated with routes', function () {
    $update = UpdateRelated::make();

    expect($update)->toBeInstanceOf(UpdateRelated::class)
        ->and($update->getRoutes())->toHaveKey('forest.related.update');
});

test('handleRequest() should return a response 200 with OneToOne relation', function () {
    $data = [
        'id'    => 2,
        'model' => 'Murcielago',
        'brand' => 'Lamborghini',
    ];
    $_GET['data'] = [
        'id'         => 2,
        'attributes' => $data,
        'type'       => 'Car',
    ];
    $update = factoryUpdateRelated(['Car' => ['listing' => $data]]);

    expect($update->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'car']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('handleRequest() should return a response 200 with ManyToOne relation', function () {
    $data = [
        'id' => 1,
    ];
    $_GET['data'] = [
        'id'         => 2,
        'attributes' => $data,
        'type'       => 'Car',
    ];
    $update = factoryUpdateRelated(['Car' => ['listing' => $data]]);

    expect($update->handleRequest(['collectionName' => 'Car', 'id' => 1, 'relationName' => 'user']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});
