<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Destroy;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

function factoryDestroy($args = []): Destroy
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'Europe/Paris';

    $collectionCars = new Collection($datasource, 'Car');
    $collectionCars->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'model' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

//    if (isset($args['destroy'])) {
//        $collectionCars = mock($collectionCars)
//            ->shouldReceive('aggregate')
//            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class))
//            ->andReturn(($args['destroy']))
//            ->getMock();
//    }

    $datasource->addCollection($collectionCars);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasources([$datasource]);

    $request = Request::createFromGlobals();
    $permissions = new Permissions(QueryStringParser::parseCaller($request));

    Cache::put(
        $permissions->getCacheKey(10),
        collect(
            [
                'actions' => collect(
                    [
                        'delete:Car' => collect([1]),
                    ]
                ),
                'scopes'  => collect(),
            ]
        ),
        300
    );

    $destroy = mock(Destroy::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->andReturnNull()
        ->getMock();

    invokeProperty($destroy, 'request', $request);

    return $destroy;
}

test('make() should return a new instance of Destroy with routes', function () {
    $destroy = Destroy::make();

    expect($destroy)->toBeInstanceOf(Destroy::class)
        ->and($destroy->getRoutes())->toHaveKey('forest.destroy');
});

test('handleRequest() should return a response 200', function () {
//    $data = [
//        [
//            'id'    => 1,
//            'model' => 'F8',
//            'brand' => 'Ferrari',
//        ],
//        [
//            'id'    => 2,
//            'model' => 'Aventador',
//            'brand' => 'Lamborghini',
//        ],
//    ];

    $destroy = factoryDestroy(
//        [
//            'destroy' => $data,
//        ]
    );

    expect($destroy->handleRequest(['collectionName' => 'Car', 'id' => 1]))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('handleRequestBulk() should return a response 200', function () {
    $_GET['data'] = [
        'attributes' => [
            'ids'                      => ['1','2','3'],
            'collection_name'          => 'Car',
            'parent_collection_name'   => null,
            'parent_collection_id'     => null,
            'parent_association_name'  => null,
            'all_records'              => true,
            'all_records_subset_query' => [
                'fields[Car]'      => 'id,model,brand',
                'page[number]'     => 1,
                'page[size]'       => 15,
            ],
            'all_records_ids_excluded' => [],
            'smart_action_id'          => null,
        ],
        'type'       => 'action-requests',
    ];

    $destroy = factoryDestroy();

    expect($destroy->handleRequestBulk(['collectionName' => 'Car']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});
