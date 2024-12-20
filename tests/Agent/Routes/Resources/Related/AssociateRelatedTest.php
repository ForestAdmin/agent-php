<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\AssociateRelated;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

$before = static function (TestCase $testCase, $args = []) {
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'cars'       => new OneToManySchema(
                originKey: 'user_id',
                originKeyTarget: 'id',
                foreignCollection: 'Car',
            ),
            'houses'     => new ManyToManySchema(
                originKey: 'user_id',
                originKeyTarget: 'id',
                foreignKey: 'house_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'House',
                throughCollection: 'HouseUser'
            ),
            'comments' => new PolymorphicOneToManySchema(
                originKey: 'commentableId',
                originKeyTarget: 'id',
                foreignCollection: 'Comment',
                originTypeField: 'commentableType',
                originTypeValue: 'User',
            ),
        ]
    );

    $collectionHouse = new Collection($datasource, 'House');
    $collectionHouse->addFields(
        [
            'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'address'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'zip_code' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'city'     => new ColumnSchema(columnType: PrimitiveType::STRING),
            'users'    => new ManyToManySchema(
                originKey: 'house_id',
                originKeyTarget: 'id',
                foreignKey: 'user_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
                throughCollection: 'HouseUser',
            ),
        ]
    );

    $collectionHouseUser = new Collection($datasource, 'HouseUser');
    $collectionHouseUser->addFields(
        [
            'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'house_id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL]),
            'user_id'  => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL]),
            'house'    => new ManyToOneSchema(
                foreignKey: 'house_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'House',
            ),
            'user'     => new ManyToOneSchema(
                foreignKey: 'user_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
        ]
    );
    $collectionHouseUser = \Mockery::mock($collectionHouseUser)
        ->shouldReceive('create')
        ->andReturn([])
        ->getMock();

    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
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

    $collectionComment = new Collection($datasource, 'Comment');
    $collectionComment->addFields(
        [
            'id'              => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'title'           => new ColumnSchema(columnType: PrimitiveType::STRING),
            'commentableId'   => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'commentableType' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'commentable'     => new PolymorphicManyToOneSchema(
                foreignKeyTypeField: 'commentableType',
                foreignKey: 'commentableId',
                foreignKeyTargets: [
                    'Car'   => 'id',
                    'User'  => 'id',
                ],
                foreignCollections: [
                    'Car',
                    'User',
                ],
            ),]
    );

    if (isset($args['associate'])) {
        $collectionUser = \Mockery::mock($collectionUser)
            ->shouldReceive('associate')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Filter::class), \Mockery::type(RelationSchema::class))
            ->andReturnNull()
            ->getMock();
    }

    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionCar);
    $datasource->addCollection($collectionHouse);
    $datasource->addCollection($collectionHouseUser);
    $datasource->addCollection($collectionComment);
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
                'edit'  => [
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

    $associate = \Mockery::mock(AssociateRelated::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    $testCase->invokeProperty($associate, 'request', $request);

    return $associate;
};

test('make() should return a new instance of AssociateRelated with routes', function () {
    $associate = AssociateRelated::make();

    expect($associate)->toBeInstanceOf(AssociateRelated::class)
        ->and($associate->getRoutes())->toHaveKey('forest.related.associate');
});

test('handleRequest() should return a response 200', function () use ($before) {
    $_GET['data'] = [
        [
            'id'   => 1,
            'type' => 'Car',
        ],
        [
            'id'   => 2,
            'type' => 'Car',
        ],
    ];
    $associate = $before($this, ['associate' => true]);

    expect($associate->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'cars']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('handleRequest() should return a response 200 with ManyToMany', function () use ($before) {
    $_GET['data'] = [
        [
            'id'   => 1,
            'type' => 'House',
        ],
    ];
    $associate = $before($this, ['associate' => true]);

    expect($associate->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'houses']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('handleRequest() should return a response 200 with PolymorphicOneToMany', function () use ($before) {
    $_GET['data'] = [
        [
            'id'   => 1,
            'type' => 'Comment',
        ],
    ];
    $associate = $before($this, ['associate' => true]);

    expect($associate->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'comments']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});
