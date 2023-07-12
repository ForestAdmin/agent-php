<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Destroy;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

$before = static function (TestCase $testCase) {
    $datasource = new Datasource();
    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'model' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $datasource->addCollection($collectionCar);
    $testCase->buildAgent($datasource);

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
                'delete'  => [
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
    $destroy = mock(Destroy::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($destroy, 'request', $request);

    return $destroy;
};

test('make() should return a new instance of Destroy with routes', function () {
    $destroy = Destroy::make();

    expect($destroy)->toBeInstanceOf(Destroy::class)
        ->and($destroy->getRoutes())->toHaveKey('forest.destroy');
});

test('handleRequest() should return a response 200', function () use ($before) {
    $destroy = $before($this);

    expect($destroy->handleRequest(['collectionName' => 'Car', 'id' => 1]))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('handleRequestBulk() should return a response 200', function () use ($before) {
    $_GET['data'] = [
        'attributes' => [
            'ids'                      => ['1', '2', '3'],
            'collection_name'          => 'Car',
            'parent_collection_name'   => null,
            'parent_collection_id'     => null,
            'parent_association_name'  => null,
            'all_records'              => true,
            'all_records_subset_query' => [
                'fields[Car]'  => 'id,model,brand',
                'page[number]' => 1,
                'page[size]'   => 15,
            ],
            'all_records_ids_excluded' => [],
            'smart_action_id'          => null,
        ],
        'type'       => 'action-requests',
    ];

    $destroy = $before($this);

    expect($destroy->handleRequestBulk(['collectionName' => 'Car']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});
