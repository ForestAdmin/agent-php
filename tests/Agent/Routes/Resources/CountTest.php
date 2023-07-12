<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Count;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\SchemaCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

$before = static function (TestCase $testCase, $args = []) {
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
            ->andReturn($args['count'])
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
            'User' => [
                'browse'  => [
                    0 => 1,
                ],
                'export'  => [
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


    $count = mock(Count::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($count, 'request', $request);

    return $count;
};

test('make() should return a new instance of Count with routes', function () {
    $count = Count::make();

    expect($count)->toBeInstanceOf(Count::class)
        ->and($count->getRoutes())->toHaveKey('forest.count');
});

test('handleRequest() should return a response 200', function () use ($before) {
    $data = [
        [
            'value' => 2,
            'group' => [],
        ],
    ];

    $count = $before($this, ['count' => $data]);

    expect($count->handleRequest(['collectionName' => 'User']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => [
                    'count' => $data[0]['value'],
                ],
            ]
        );
});

test('handleRequest() should return deactivate count when the collecion is not countable', function () use ($before) {
    $count = $before($this, ['countDisable' => true]);

    expect($count->handleRequest(['collectionName' => 'User']))->toEqual([
        'content' => [
            'meta' => [
                'count' => 'deactivated',
            ],
        ],
    ]);
});
