<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Show;
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

use Mockery;

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

    if (isset($args['show'])) {
        $collectionCar = Mockery::mock($collectionCar)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn(($args['show']))
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
                'read'  => [
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

    $show = Mockery::mock(Show::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    $testCase->invokeProperty($show, 'request', $request);

    return $show;
};

test('make() should return a new instance of Show with routes', function () {
    $show = Show::make();

    expect($show)->toBeInstanceOf(Show::class)
        ->and($show->getRoutes())->toHaveKey('forest.show');
});

test('handleRequest() should return a response 200', function () use ($before) {
    $data = [
        [
            'id'    => 1,
            'model' => 'F8',
            'brand' => 'Ferrari',
        ],
    ];
    $show = $before($this, ['show' => $data]);

    expect($show->handleRequest(['collectionName' => 'Car', 'id' => 1]))
        ->toBeArray()
        ->toEqual(
            [
                'name'    => 'Car',
                'content' => [
                    'data' => [
                        'type'       => 'Car',
                        'id'         => '1',
                        'attributes' => [
                            'model' => 'F8',
                            'brand' => 'Ferrari',
                        ],
                    ],
                ],
            ]
        );
});
