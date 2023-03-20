<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Store;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factoryStore($args = []): Store
{
    $datasource = new Datasource();
    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'model' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand' => new ColumnSchema(columnType: PrimitiveType::STRING),
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
    buildAgent($datasource);

    SchemaEmitter::getSerializedSchema($datasource);

    $request = Request::createFromGlobals();
    $permissions = new Permissions(QueryStringParser::parseCaller($request));

    Cache::put(
        $permissions->getCacheKey(10),
        collect(
            [
                'actions' => collect(
                    [
                        'add:Car' => collect([1]),
                    ]
                ),
                'scopes'  => collect(),
            ]
        ),
        300
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
        'attributes' => $data,
        'type'       => 'Car',
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
