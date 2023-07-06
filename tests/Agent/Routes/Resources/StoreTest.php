<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Store;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;

use function ForestAdmin\config;

function factoryStore($args = []): Store
{
    $datasource = new Datasource();
    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'model'    => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand'    => new ColumnSchema(columnType: PrimitiveType::STRING),
            'owner_id' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'owner'    => new ManyToOneSchema(
                foreignKey: 'owner_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Owner',
            ),
        ]
    );

    $collectionOwner = new Collection($datasource, 'Owner');
    $collectionOwner->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    if (isset($args['store'])) {
        $collectionCar = mock($collectionCar)
            ->shouldReceive('create')
            ->with(\Mockery::type(Caller::class), \Mockery::type('array'))
            ->andReturn(($args['store']))
            ->getMock();
    }

    $datasource->addCollection($collectionCar);
    $datasource->addCollection($collectionOwner);
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
            'Car' => [
                'add'  => [
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

    $store = mock(Store::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($store, 'request', $request);

    return $store;
}

test('make() should return a new instance of Store with routes', function () {
    $store = Store::make();

    expect($store)->toBeInstanceOf(Store::class)
        ->and($store->getRoutes())->toHaveKey('forest.create');
});

test('handleRequest() should return a response 200', function () {
    $data = [
        'id'    => 2,
        'model' => 'Aventador',
        'brand' => 'Lamborghini',
    ];
    $_GET['data'] = [
        'attributes'    => $data,
        'type'          => 'Car',
        'relationships' => [
            'owner' => [
                'data' => [
                    'type' => 'Owner',
                    'id'   => 1,
                ],
            ],
        ],
    ];
    $store = factoryStore(['store' => $data]);

    expect($store->handleRequest(['collectionName' => 'Car']))
        ->toBeArray()
        ->toEqual(
            [
                'name'    => 'Car',
                'content' => [
                    'data' => [
                        'type'       => 'Car',
                        'id'         => '2',
                        'attributes' => [
                            'model' => 'Aventador',
                            'brand' => 'Lamborghini',
                        ],
                    ],
                ],
            ]
        );
});
