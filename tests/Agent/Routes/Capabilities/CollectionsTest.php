<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Capabilities\Collections;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

$before = static function (TestCase $testCase) {
    $datasource = new Datasource();
    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'model'    => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::IN, Operators::EQUAL]),
            'brand'    => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::IN, Operators::EQUAL]),
            'price'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::GREATER_THAN, Operators::LESS_THAN]),
            'owner_id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL]),
            'owner'    => new ManyToOneSchema(
                foreignKey: 'owner_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Owner',
            ),
        ]
    );

    $collectionOwner = new Collection($datasource, 'Owner');
    $collectionOwner->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::IN, Operators::EQUAL]),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::IN, Operators::EQUAL]),
        ]
    );

    $datasource->addCollection($collectionCar);
    $datasource->addCollection($collectionOwner);
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

    $collections = \Mockery::mock(Collections::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    $testCase->invokeProperty($collections, 'request', $request);

    return $collections;
};

test('make() should return a new instance of Collections with routes', function () {
    $collections = Collections::make();

    expect($collections)->toBeInstanceOf(Collections::class)
        ->and($collections->getRoutes())->toHaveKey('forest.capabilities.collections');
});

test('when there is no collectionNames in params return all the collections', function () use ($before) {
    $call = $before($this);
    expect($call->handleRequest())
        ->toBeArray()
        ->toEqual(
            [
                'content' => [
                    'collections' => [
                        [
                            'name'   => 'Car',
                            'fields' => [
                                [
                                    'name'      => 'id',
                                    'type'      => 'Number',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                                [
                                    'name'      => 'model',
                                    'type'      => 'String',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                                [
                                    'name'      => 'brand',
                                    'type'      => 'String',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                                [
                                    'name'      => 'price',
                                    'type'      => 'Number',
                                    'operators' => [
                                        'Equal',
                                        'GreaterThan',
                                        'LessThan',
                                    ],
                                ],
                                [
                                    'name'      => 'owner_id',
                                    'type'      => 'Number',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name'   => 'Owner',
                            'fields' => [
                                [
                                    'name'      => 'id',
                                    'type'      => 'Number',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                                [
                                    'name'      => 'first_name',
                                    'type'      => 'String',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                                [
                                    'name'      => 'last_name',
                                    'type'      => 'String',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'status' => 200,
            ]
        );
});

test('when there is collectionNames in params return the collections provided', function () use ($before) {
    $_POST = [
        'collectionNames' => ['Owner'],
    ];
    $call = $before($this);
    expect($call->handleRequest())
        ->toBeArray()
        ->toEqual(
            [
                'content' => [
                    'collections' => [
                        [
                            'name'   => 'Owner',
                            'fields' => [
                                [
                                    'name'      => 'id',
                                    'type'      => 'Number',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                                [
                                    'name'      => 'first_name',
                                    'type'      => 'String',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                                [
                                    'name'      => 'last_name',
                                    'type'      => 'String',
                                    'operators' => [
                                        'In',
                                        'Equal',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'status' => 200,
            ]
        );
});

test('when there is collectionNames in params and the collection does not exist it throws an exception', function () use ($before) {
    $_POST = [
        'collectionNames' => ['Foo'],
    ];
    $call = $before($this);
    expect(fn () => $call->handleRequest())
        ->toThrow(ForestException::class, '🌳🌳🌳 Collection Foo not found.');
});
