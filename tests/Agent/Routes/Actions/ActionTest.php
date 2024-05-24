<?php

use Firebase\JWT\JWT;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Actions\Actions;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ResultBuilder;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

function factoryAction(TestCase $testCase, $smartAction): Actions
{
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'categories' => new OneToManySchema(
                originKey: 'category_id',
                originKeyTarget: 'id',
                foreignCollection: 'Category',
            ),
        ]
    );

    $collectionCategory = new Collection($datasource, 'Category');
    $collectionCategory->addFields(
        [
            'id'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'rate' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'user' => new ManyToOneSchema(
                foreignKey: 'user_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
        ]
    );

    $datasource->addCollection($collectionCategory);
    $datasource->addCollection($collectionUser);
    $testCase->buildAgent($datasource);

    $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);
    $datasourceDecorator->build();

    $collection = $datasourceDecorator->getCollection('User');
    $collection->addAction($smartAction[0], $smartAction[1]);

    SchemaEmitter::getSerializedSchema($datasource);

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
                'actions' => [
                    'my action' => [
                        "triggerEnabled"             => [1],
                        "approvalRequired"           => [],
                        "approvalRequiredConditions" => [],
                        "userApprovalEnabled"        => [],
                        "userApprovalConditions"     => [],
                        "selfApprovalEnabled"        => [],
                    ],
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

    Cache::put(
        'forest.has_permission',
        true,
        config('permissionExpiration')
    );

    $action = \Mockery::mock(Actions::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->shouldReceive('build')
        ->getMock();

    $permissions = \Mockery::mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getCollectionsPermissionsData')
        ->andReturn(
            [
                'User' => [
                    'browse'  => [
                        0 => 1,
                    ],
                    'export'  => [
                        0 => 1,
                    ],
                    'actions' => [
                        'my action' => [
                            'triggerEnabled'             => [1],
                            'approvalRequired'           => [],
                            'approvalRequiredConditions' => [],
                            'userApprovalEnabled'        => [],
                            'userApprovalConditions'     => [],
                            'selfApprovalEnabled'        => [],
                        ],
                    ],
                ],
            ]
        )
        ->shouldReceive('findActionFromEndpoint')
        ->andReturn(
            [
                'id'         => 'my-action',
                'name'       => 'my action',
                'type'       => 'single',
                'endpoint'   => '/forest/_actions/User/0/my-action',
                'httpMethod' => 'POST',
                'redirect'   => null,
                'download'   => false,
                'fields'     => [],
                'hooks'      => [],
            ]
        )
        ->getMock();

    $request = Request::createFromGlobals();
    $testCase->invokeProperty($permissions, 'caller', QueryStringParser::parseCaller($request));

    $testCase->invokeProperty($action, 'caller', QueryStringParser::parseCaller($request));
    $testCase->invokeProperty($action, 'actionName', 'my action');
    $testCase->invokeProperty($action, 'permissions', $permissions);
    $testCase->invokeProperty($action, 'collection', $collection);

    return $action;
}

test('when a action is create, the action route should return the appropriate routes', function () {
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'User');
    $datasource->addCollection($collectionUser);
    $this->buildAgent($datasource);

    $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);
    $datasourceDecorator->build();

    $collection = $datasourceDecorator->getCollection('User');
    $closure = fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO');
    $collection->addAction('my action', new BaseAction(
        'SINGLE',
        $closure
    ));

    $action = new Actions($collection, 'my action');
    expect($action->getRoutes())->toBeArray()
        ->and($action->getRoutes()['forest.action.User.0.my-action'])->toEqual(
            [
                'methods' => 'post',
                'uri'     => '/_actions/User/0/my-action',
                'closure' => $closure,
            ]
        )
        ->and($action->getRoutes()['forest.action.User.0.my-action.load'])->toEqual(
            [
                'methods' => 'post',
                'uri'     => '/_actions/User/0/my-action/hooks/load',
                'closure' => $closure,
            ]
        )
        ->and($action->getRoutes()['forest.action.User.0.my-action.change'])->toEqual(
            [
                'methods' => 'post',
                'uri'     => '/_actions/User/0/my-action/hooks/change',
                'closure' => $closure,
            ]
        );
});

test('handleRequest should return the result of an action', function () {
    $type = 'SINGLE';
    $smartAction = [
        'my action',
        new BaseAction($type, fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO')),
    ];
    $action = factoryAction($this, $smartAction);

    $data = [
        'data' => [
            'attributes' => [
                'values'                   => [],
                'ids'                      => [
                    '50',
                ],
                'collection_name'          => 'User',
                'parent_collection_name'   => null,
                'parent_collection_id'     => null,
                'parent_association_name'  => null,
                'all_records'              => false,
                'all_records_subset_query' => [
                    'timezone' => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'User-my@@@action',
                'signed_approval_request'  => null,
                'changed_field'            => null,
            ],
            'type'       => 'custom-action-requests',
        ],
    ];

    $_POST = $data;

    $this->invokeProperty($action, 'request', Request::createFromGlobals());
    $this->invokeProperty($action, 'action', new BaseAction($type, fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO')));

    expect($action->handleRequest(['collectionName' => 'User']))->toEqual((new ResultBuilder())->success('BRAVO'));
});

test('handleRequest with signed approval request should put the decoded request to the request class', function () {
    $type = 'SINGLE';
    $smartAction = [
        'my action',
        new BaseAction($type, fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO')),
    ];
    $action = factoryAction($this, $smartAction);

    $data = [
        'data' => [
            'attributes' => [
                'values'                   => [],
                'ids'                      => [
                    '50',
                ],
                'collection_name'          => 'User',
                'parent_collection_name'   => null,
                'parent_collection_id'     => null,
                'parent_association_name'  => null,
                'all_records'              => false,
                'all_records_subset_query' => [
                    'timezone' => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'User-my@@@action',
                'signed_approval_request'  => null,
                'changed_field'            => null,
            ],
            'type'       => 'custom-action-requests',
        ],
    ];
    $encodedRequest = JWT::encode($data, config('envSecret'), 'HS256');
    $data['data']['attributes']['signed_approval_request'] = $encodedRequest;

    $_POST = $data;

    $this->invokeProperty($action, 'request', Request::createFromGlobals());
    $this->invokeProperty($action, 'action', new BaseAction($type, fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO')));

    expect($action->handleRequest(['collectionName' => 'User']))->toEqual((new ResultBuilder())->success('BRAVO'));
});

test('handleHookRequest should return the result of an action', function () {
    $type = 'GLOBAL';
    $smartAction = [
        'my action',
        new BaseAction(
            scope: $type,
            execute: fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO'),
            form: [
                new DynamicField(type: FieldType::NUMBER, label: 'amount'),
                new DynamicField(type: FieldType::STRING, label: 'description', isRequired: true),
                new DynamicField(
                    type: FieldType::STRING,
                    label: 'amount X10',
                    isReadOnly: true,
                    value: function ($context) {
                        return $context->getFormValue('amount') * 10;
                    }
                ),
            ]
        ),
    ];
    $action = factoryAction($this, $smartAction);

    $data = [
        'data' => [
            'attributes' => [
                'fields'                   => [
                    [
                        'field'         => 'first_name',
                        'type'          => 'String',
                        'reference'     => null,
                        'enums'         => null,
                        'description'   => null,
                        'isRequired'    => false,
                        'value'         => 10,
                        'previousValue' => null,
                        'isReadOnly'    => false,
                        'hook'          => 'changeHook',
                    ],
                ],
                'changed_field'            => 'last_name',
                'ids'                      => [
                    '50',
                ],
                'collection_name'          => 'User',
                'parent_collection_name'   => null,
                'parent_collection_id'     => null,
                'parent_association_name'  => null,
                'all_records'              => true,
                'all_records_ids_excluded' => [1, 2, 3],
                'all_records_subset_query' => [
                    'timezone' => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'User-Change@@@my@@@action',
                'signed_approval_request'  => null,
                'changed_field'            => null,
            ],
            'type'       => 'custom-action-hook-requests',
        ],
    ];

    $_POST = $data;
    $this->invokeProperty($action, 'request', Request::createFromGlobals());
    $this->invokeProperty($action, 'action', new BaseAction($type, fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO')));


    expect($action->handleHookRequest(['collectionName' => 'User']))->toBeArray()
        ->and($action->handleHookRequest(['collectionName' => 'User']))->toHaveKey('content')
        ->and($action->handleHookRequest(['collectionName' => 'User'])['content']['fields'])->toEqual(
            collect(
                [
                    [
                        'description' => null,
                        'isRequired'  => false,
                        'isReadOnly'  => false,
                        'field'       => 'amount',
                        'value'       => null,
                        'type'        => 'Number',
                    ],
                    [
                        'description' => null,
                        'isRequired'  => true,
                        'isReadOnly'  => false,
                        'field'       => 'description',
                        'value'       => null,
                        'type'        => 'String',
                    ],
                    [
                        'description' => null,
                        'isRequired'  => false,
                        'isReadOnly'  => true,
                        'field'       => 'amount X10',
                        'value'       => '0',
                        'type'        => 'String',
                    ],
                ]
            )
        );
});

test('handleHookRequest should return the result of an action on a association', function () {
    $type = 'GLOBAL';
    $smartAction = [
        'my action',
        new BaseAction(
            scope: $type,
            execute: fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO'),
            form: [
                new DynamicField(type: FieldType::NUMBER, label: 'rate'),
            ]
        ),
    ];
    $action = factoryAction($this, $smartAction);

    $data = [
        'data' => [
            'attributes' => [
                'fields'                   => [
                    [
                        'field'         => 'rate',
                        'type'          => 'Number',
                        'reference'     => null,
                        'enums'         => null,
                        'description'   => null,
                        'isRequired'    => false,
                        'value'         => 10,
                        'previousValue' => null,
                        'isReadOnly'    => false,
                        'hook'          => 'changeHook',
                    ],
                ],
                'changed_field'            => 'rate',
                'ids'                      => [
                    '50',
                ],
                'collection_name'          => 'Category',
                'parent_collection_name'   => 'User',
                'parent_collection_id'     => '1',
                'parent_association_name'  => 'categories',
                'all_records'              => true,
                'all_records_ids_excluded' => [1, 2, 3],
                'all_records_subset_query' => [
                    'timezone' => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'User-Change@@@my@@@action',
                'signed_approval_request'  => null,
                'changed_field'            => null,
            ],
            'type'       => 'custom-action-hook-requests',
        ],
    ];

    $_POST = $data;
    $this->invokeProperty($action, 'request', Request::createFromGlobals());
    $this->invokeProperty($action, 'action', new BaseAction($type, fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO')));

    expect($action->handleHookRequest(['collectionName' => 'User']))->toBeArray()
        ->and($action->handleHookRequest(['collectionName' => 'User']))->toHaveKey('content')
        ->and($action->handleHookRequest(['collectionName' => 'User'])['content']['fields'])->toEqual(
            collect(
                [
                    [
                        'description' => null,
                        'isRequired'  => false,
                        'isReadOnly'  => false,
                        'field'       => 'rate',
                        'value'       => 10,
                        'type'        => 'Number',
                    ],
                ]
            )
        );
});
