<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Show;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

function factoryShow($args = []): Show
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'Europe/Paris';

    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'model' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    if (isset($args['show'])) {
        $collectionCar = mock($collectionCar)
            ->shouldReceive('show')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), [$args['show']['id']], \Mockery::type(Projection::class))
            ->andReturn(($args['show']))
            ->getMock();
    }

    $datasource->addCollection($collectionCar);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'schemaPath'   => sys_get_temp_dir() . '/.forestadmin-schema.json',
        'envSecret'    => SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasources([$datasource]);
    SchemaEmitter::getSerializedSchema($datasource);

    $request = Request::createFromGlobals();
    $permissions = new Permissions(QueryStringParser::parseCaller($request));

    Cache::put(
        $permissions->getCacheKey(10),
        collect(
            [
                'actions' => collect(
                    [
                        'read:Car' => collect([1]),
                    ]
                ),
                'scopes'  => collect(),
            ]
        ),
        300
    );

    $show = mock(Show::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($show, 'request', $request);

    return $show;
}

test('make() should return a new instance of Show with routes', function () {
    $show = Show::make();

    expect($show)->toBeInstanceOf(Show::class)
        ->and($show->getRoutes())->toHaveKey('forest.show');
});

test('handleRequest() should return a response 200', function () {
    $data = [
        'id'    => 1,
        'model' => 'F8',
        'brand' => 'Ferrari',
    ];
    $show = factoryShow(
        [
            'show'   => $data,
        ]
    );

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

