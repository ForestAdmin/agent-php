<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\ListingRelated;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;

function factoryListingRelated($args = []): ListingRelated
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

    if (isset($args['listing'])) {
        $collectionCar = mock($collectionCar)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn($args['listing'])
            ->getMock();
    }

    if (isset($args['export'])) {
        $collectionCar = mock($collectionCar)
            ->shouldReceive('export')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn($args['export'])
            ->getMock();
    }

    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionCar);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'schemaPath'   => sys_get_temp_dir() . '/.forestadmin-schema.json',
        'authSecret'    => AUTH_SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();
    SchemaEmitter::getSerializedSchema($datasource);

    $request = Request::createFromGlobals();
    $permissions = new Permissions(QueryStringParser::parseCaller($request));

    Cache::put(
        $permissions->getCacheKey(10),
        collect(
            [
                'actions' => collect(
                    [
                        'browse:Car' => collect([1]),
                        'export:Car' => collect([1]),
                    ]
                ),
                'scopes'  => collect(),
            ]
        ),
        300
    );

    $listing = mock(ListingRelated::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($listing, 'request', $request);

    return $listing;
}

test('make() should return a new instance of ListingRelated with routes', function () {
    $listing = ListingRelated::make();

    expect($listing)->toBeInstanceOf(ListingRelated::class)
        ->and($listing->getRoutes())->toHaveKey('forest.related.list');
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
    $listing = factoryListingRelated(['listing' => $data]);
    expect($listing->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'cars']))
        ->toBeArray()
        ->toEqual(
            [
                'name'    => 'Car',
                'content' => [
                    'data' => [
                        ['id'         => 1,
                         'type'       => 'Car',
                         'attributes' => [
                             'model'   => 'F8',
                             'brand'   => 'Ferrari',
                             'user_id' => 1,
                         ],
                        ],
                        [
                            'id'         => 2,
                            'type'       => 'Car',
                            'attributes' => [
                                'model'   => 'Aventador',
                                'brand'   => 'Lamborghini',
                                'user_id' => 1,
                            ],
                        ],
                    ],
                ],
            ]
        );
});

test('handleRequestCsv() should return a response 200', function () {
    $_GET['filename'] = 'export-cars';
    $_GET['header'] = 'id,model,brand,user_id';
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

    $listing = factoryListingRelated(['export' => $data]);

    expect($listing->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'cars.csv']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => "id,model,brand,user_id\n1,F8,Ferrari,1\n2,Aventador,Lamborghini,1\n",
                'headers' => [
                    'Content-type'        => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $_GET['filename'] . '.csv"',
                ],
            ]
        );
});
