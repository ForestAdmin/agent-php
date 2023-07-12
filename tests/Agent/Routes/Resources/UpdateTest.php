<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Update;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

$before = static function (TestCase $testCase, $args = []) {
    $datasource = new Datasource();
    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'model' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    if (isset($args['update'])) {
        $collectionCar = mock($collectionCar)
            ->shouldReceive('update')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type('array'))
            ->andReturn($args['update'])
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn([$args['update']])
            ->getMock();
    }

    $datasource->addCollection($collectionCar);
    $testCase->buildAgent($datasource);

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

    $update = mock(Update::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();
    invokeProperty($update, 'request', $request);

    return $update;
};

test('make() should return a new instance of Update with routes', function () {
    $update = Update::make();

    expect($update)->toBeInstanceOf(Update::class)
        ->and($update->getRoutes())->toHaveKey('forest.update');
});

test('handleRequest() should return a response 200', function () use ($before) {
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

    $update = $before($this, ['update' => $data]);

    expect($update->handleRequest(['collectionName' => 'Car', 'id' => 2]))
        ->toBeArray()
        ->toEqual(
            [
                'name'    => 'Car',
                'content' => [
                    'data' => [
                        'type'       => 'Car',
                        'id'         => '2',
                        'attributes' => [
                            'model' => 'Murcielago',
                            'brand' => 'Lamborghini',
                        ],
                    ],
                ],
            ]
        );
});
