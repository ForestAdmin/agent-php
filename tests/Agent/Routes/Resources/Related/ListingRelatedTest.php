<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\ListingRelated;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
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
            'cars'       => new OneToManySchema(
                originKey: 'user_id',
                originKeyTarget: 'id',
                foreignCollection: 'Car',
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
            ),
        ]
    );

    if (isset($args['listing'])) {
        $_GET['fields']['Car'] = implode(',', array_keys($args['listing'][0]));
        $collectionCar = \Mockery::mock($collectionCar)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type(Projection::class))
            ->andReturn($args['listing'])
            ->getMock();
    }

    $datasource->addCollection($collectionUser);
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
                'browse'  => [
                    0 => 1,
                ],
                'export'  => [
                    0 => 1,
                ],
            ],
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
        'forest.rendering',
        collect(
            [
                'scopes' => [],
                'team'   => [
                    'id'   => 44,
                    'name' => 'Operations',
                ],
            ]
        ),
        config('permissionExpiration')
    );

    $listing = \Mockery::mock(ListingRelated::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    $testCase->invokeProperty($listing, 'request', $request);

    return $listing;
};

test('make() should return a new instance of ListingRelated with routes', function () {
    $listing = ListingRelated::make();

    expect($listing)->toBeInstanceOf(ListingRelated::class)
        ->and($listing->getRoutes())->toHaveKey('forest.related.list');
});

test('handleRequest() should return a response 200', function () use ($before) {
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
    $listing = $before($this, ['listing' => $data]);

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

test('handleRequestCsv() should return a response 200', function () use ($before) {
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

    $listing = $before($this, ['listing' => $data]);

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
