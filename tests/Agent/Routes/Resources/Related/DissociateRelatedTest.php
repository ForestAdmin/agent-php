<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\DissociateRelated;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

function factoryDissociateRelated($args = []): DissociateRelated
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'Europe/Paris';

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
                inverseRelationName: 'user',
            ),
            'houses'     => new ManyToManySchema(
                originKey: 'user_id',
                originKeyTarget: 'id',
                throughTable: 'houses_users',
                foreignKey: 'house_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'House',
                inverseRelationName: 'users'
            ),
        ]
    );

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
                inverseRelationName: 'cars'
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
                throughTable: 'houses_users',
                foreignKey: 'user_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
                inverseRelationName: 'houses'
            ),
        ]
    );

    if (isset($args['dissociate'])) {
        $collectionUser = mock($collectionUser)
            ->shouldReceive('dissociate')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Filter::class), \Mockery::type(RelationSchema::class))
            ->andReturnNull()
            ->getMock();
    }

    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionCar);
    $datasource->addCollection($collectionHouse);

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
                        'delete:User'  => collect([1]),
                        'delete:House' => collect([1]),
                    ]
                ),
                'scopes'  => collect(),
            ]
        ),
        300
    );

    $dissociate = mock(DissociateRelated::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($dissociate, 'request', $request);

    return $dissociate;
}

test('make() should return a new instance of DissociateRelated with routes', function () {
    $dissociate = DissociateRelated::make();

    expect($dissociate)->toBeInstanceOf(DissociateRelated::class)
        ->and($dissociate->getRoutes())->toHaveKey('forest.related.dissociate');
});

test('handleRequest() on ManyToOneSchema relation should return a response 200', function () {
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
    $dissociate = factoryDissociateRelated(['dissociate' => true]);

    expect($dissociate->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'cars']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('Delete mode on handleRequest() to a ManyToOneSchema relation should return a response 200', function () {
    $_GET['delete'] = true;
    $_GET['data'] = [
        'attributes' => [
            'ids'                      => [
                ['id' => '1', 'type' => 'car'],
                ['id' => '2', 'type' => 'car'],
            ],
            'collection_name'          => 'Car',
            'parent_collection_name'   => 'User',
            'parent_collection_id'     => '1',
            'parent_association_name'  => 'cars',
            'all_records'              => true,
            'all_records_subset_query' => [
                'fields[Car]'  => 'id,model,brand',
                'page[number]' => 1,
                'page[size]'   => 15,
            ],
            'all_records_ids_excluded' => ['3'],
            'smart_action_id'          => null,
        ],
        'type'       => 'action-requests',
    ];
    $dissociate = factoryDissociateRelated(['dissociate' => true]);

    expect($dissociate->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'cars']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('Delete mode on handleRequest() to a ManyToManySchema relation should return a response 200', function () {
    $_GET['delete'] = true;
    $_GET['data'] = [
        [
            'id'   => 1,
            'type' => 'House',
        ],
        [
            'id'   => 2,
            'type' => 'House',
        ],
    ];
    $dissociate = factoryDissociateRelated(['dissociate' => true]);

    expect($dissociate->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'houses']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('handleRequest() should throw if there is no ids', function () {
    $_GET['data'] = [];
    $dissociate = factoryDissociateRelated(['dissociate' => true]);

    expect(fn () => $dissociate->handleRequest(['collectionName' => 'User', 'id' => 1, 'relationName' => 'cars']))
        ->toThrow(ForestException::class, '🌳🌳🌳 Expected no empty id list');
});

