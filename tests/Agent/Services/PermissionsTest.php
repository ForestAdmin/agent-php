<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ForbiddenError;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

use function ForestAdmin\config;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Prophecy\Argument;
use Prophecy\Prophet;

function permissionsFactory($post = [], $roleId = 1, $userRoleId = 1)
{
    $_POST = $post;
    $datasource = new Datasource();
    $collectionBooking = new Collection($datasource, 'Booking');
    $collectionBooking->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $datasource->addCollection($collectionBooking);
    buildAgent($datasource);


    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
        ->get('/liana/v4/permissions/users')
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode([
                0 => [
                    'id'              => 1,
                    'firstName'       => 'John',
                    'lastName'        => 'Doe',
                    'fullName'        => 'John Doe',
                    'email'           => 'john.doe@domain.com',
                    'tags'            => [],
                    'roleId'          => $userRoleId,
                    'permissionLevel' => 'admin',
                ],
                1 => [
                    'id'              => 3,
                    'firstName'       => 'Admin',
                    'lastName'        => 'test',
                    'fullName'        => 'Admin test',
                    'email'           => 'admin@forestadmin.com',
                    'tags'            => [],
                    'roleId'          => 13,
                    'permissionLevel' => 'admin',
                ],
            ], JSON_THROW_ON_ERROR))
        );

    $forestApi
        ->get('/liana/v4/permissions/environment')
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode([
                'collections' => [
                    'Booking' => [
                        'collection' => [
                            'browseEnabled' => [
                                'roles' => [
                                    0 => 13,
                                    1 => $roleId,
                                ],
                            ],
                            'readEnabled'   => [
                                'roles' => [
                                    0 => 13,
                                    1 => $roleId,
                                ],
                            ],
                            'editEnabled'   => [
                                'roles' => [
                                    0 => 13,
                                    1 => $roleId,
                                ],
                            ],
                            'addEnabled'    => [
                                'roles' => [
                                    0 => 13,
                                    1 => $roleId,
                                ],
                            ],
                            'deleteEnabled' => [
                                'roles' => [
                                    0 => 13,
                                    1 => $roleId,
                                ],
                            ],
                            'exportEnabled' => [
                                'roles' => [
                                    0 => 13,
                                    1 => $roleId,
                                ],
                            ],
                        ],
                        'actions'    => [
                            'Mark as live' => [
                                'triggerEnabled'             => [
                                    'roles' => [
                                        0 => 13,
                                        1 => $roleId,
                                    ],
                                ],
                                'triggerConditions'          => [
                                    0 => [
                                        'filter' => [
                                            'aggregator' => 'and',
                                            'conditions' => [
                                                0 => [
                                                    'field'    => 'title',
                                                    'value'    => null,
                                                    'source'   => 'data',
                                                    'operator' => 'present',
                                                ],
                                            ],
                                        ],
                                        'roleId' => 15,
                                    ],
                                ],
                                'approvalRequired'           => [
                                    'roles' => [
                                        0 => 13,
                                        1 => $roleId,
                                    ],
                                ],
                                'approvalRequiredConditions' => [],
                                'userApprovalEnabled'        => [
                                    'roles' => [
                                        0 => 13,
                                        1 => $roleId,
                                    ],
                                ],
                                'userApprovalConditions'     => [],
                                'selfApprovalEnabled'        => [
                                    'roles' => [
                                        0 => 13,
                                        1 => $roleId,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR))
        );

    $forestApi
        ->get('/liana/v4/permissions/renderings/10')
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode([
                'collections' => [
                    'Booking' => [
                        'scope'    => [
                            'aggregator' => 'and',
                            'conditions' => [
                                0 => [
                                    'field'    => 'id',
                                    'operator' => 'greater_than',
                                    'value'    => '{{currentUser.id}}',
                                ],
                                1 => [
                                    'field'    => 'title',
                                    'operator' => 'present',
                                    'value'    => null,
                                ],
                            ],
                        ],
                        'segments' => [],
                    ],
                ],
                'stats'       => [
                    0 => [
                        'type'                 => 'Pie',
                        'filter'               => null,
                        'aggregator'           => 'Count',
                        'groupByFieldName'     => 'id',
                        'aggregateFieldName'   => null,
                        'sourceCollectionName' => 'Booking',
                    ],
                    1 => [
                        'type'                 => 'Value',
                        'filter'               => null,
                        'aggregator'           => 'Count',
                        'aggregateFieldName'   => null,
                        'sourceCollectionName' => 'Booking',
                    ],
                ],
                'team'        => [
                    'id'   => 1,
                    'name' => 'Operations',
                ],
            ], JSON_THROW_ON_ERROR))
        );


    $request = Request::createFromGlobals();
    $permissions = new Permissions(QueryStringParser::parseCaller($request));
    invokeProperty($permissions, 'forestApi', $forestApi->reveal());


    /*Cache::put(
        'forest.users',
        [
            1 => [
                'id'              => 1,
                'firstName'       => 'John',
                'lastName'        => 'Doe',
                'fullName'        => 'John Doe',
                'email'           => 'john.doe@domain.com',
                'tags'            => [],
                'roleId'          => $userRoleId,
                'permissionLevel' => 'admin',
            ],
            3 => [
                'id'              => 3,
                'firstName'       => 'Admin',
                'lastName'        => 'test',
                'fullName'        => 'Admin test',
                'email'           => 'admin@forestadmin.com',
                'tags'            => [],
                'roleId'          => 13,
                'permissionLevel' => 'admin',
            ],
        ],
        config('permissionExpiration')
    );

    Cache::put(
        'forest.collections',
        [
            'Booking' => [
                'browse'  => [
                    1 => 13,
                    2 => $roleId,
                ],
                'read'    => [
                    1 => 13,
                    2 => $roleId,
                ],
                'edit'    => [
                    1 => 13,
                    2 => $roleId,
                ],
                'add'     => [
                    1 => 13,
                    2 => $roleId,
                ],
                'delete'  => [
                    1 => 13,
                    2 => $roleId,
                ],
                'export'  => [
                    1 => 13,
                    2 => $roleId,
                ],
                'actions' => [
                    'Mark as live' => [
                        'triggerEnabled'             => [
                            0 => $roleId,
                            1 => 15,
                        ],
                        'triggerConditions'          => [
                            0 => [
                                'filter' => [
                                    'aggregator' => 'and',
                                    'conditions' => [
                                        0 => [
                                            'field'    => 'title',
                                            'value'    => null,
                                            'source'   => 'data',
                                            'operator' => 'present',
                                        ],
                                    ],
                                ],
                                'roleId' => 15,
                            ],
                        ],
                        'approvalRequired'           => [
                            0 => $roleId,
                        ],
                        'approvalRequiredConditions' => [
                            0 => [
                                'filter' => [
                                    'aggregator' => 'and',
                                    'conditions' => [
                                        0 => [
                                            'field'    => 'id',
                                            'value'    => 60,
                                            'source'   => 'data',
                                            'operator' => 'greater_than',
                                        ],
                                    ],
                                ],
                                'roleId' => $roleId,
                            ],
                        ],
                        'userApprovalEnabled'        => [
                            0 => 13,
                        ],
                        'userApprovalConditions'     => [],
                        'selfApprovalEnabled'        => [],
                    ],
                ],
            ],
        ],
        config('permissionExpiration')
    );

    Cache::put(
        'forest.has_permission',
        true,
        config('permissionExpiration')
    );

    Cache::put(
        'forest.has_permission',
        true,
        config('permissionExpiration')
    );

    Cache::put(
        'forest.scopes',
        [
            'scopes' => collect([
                'Booking' => ConditionTreeFactory::fromArray([
                    'aggregator' => 'and',
                    'conditions' => [
                        0 => [
                            'field'    => 'id',
                            'operator' => 'greater_than',
                            'value'    => '{{currentUser.id}}',
                        ],
                        1 => [
                            'field'    => 'title',
                            'operator' => 'present',
                            'value'    => null,
                        ],
                    ],
                ]),
            ]),
            'team'   => [
                'id'   => 44,
                'name' => 'Operations',
            ],
        ],
        config('permissionExpiration')
    );

    Cache::put(
        'forest.stats',
        [
            0 => 'Pie:33e92c2f6c7f40d9c824b1a88503bfee1c92902e',
            1 => 'Value:c6bccb28cf2d9ca6e62605b5169cc7e2f39d3409',
        ],
        config('permissionExpiration')
    );*/

    return [$permissions, $collectionBooking];
}

test('invalidate cache should delete the cache', function () {
    $permissions = permissionsFactory()[0];
    $permissions->invalidateCache('forest.stats');

    expect(Cache::get('forest.stats'))->toBeNull();
});

test('can() should return true when user is allowed', function () {
    [$permissions, $collection] = permissionsFactory();

    expect($permissions->can('browse', $collection))->toBeTrue();
});

test('can() should call getCollectionsPermissionsData twice', function () {
    [$permissions, $collection] = permissionsFactory();
    $request = Request::createFromGlobals();
    $mockPermissions = mock($permissions)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getCollectionsPermissionsData')
        ->andReturn(
            [
                'FakeCollection' => [
                    'browse' => [
                        0 => 1,
                    ],
                ],
            ],
            [
                'Booking' => [
                    'browse' => [
                        0 => 1,
                    ],
                ],
            ],
        )
        ->getMock();
    invokeProperty($mockPermissions, 'caller', QueryStringParser::parseCaller($request));

    expect($mockPermissions->can('browse', $collection))->toBeTrue();
});

test('can() should throw HttpException when user doesn\'t have the right access', function () {
    [$permissions, $collection] = permissionsFactory();

    $request = Request::createFromGlobals();
    $mockPermissions = mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getUserData')
        ->andReturn([
            'id'              => 1,
            'firstName'       => 'John',
            'lastName'        => 'Doe',
            'fullName'        => 'John Doe',
            'email'           => 'john.doe@domain.com',
            'tags'            => [],
            'roleId'          => 1,
            'permissionLevel' => 'admin',
        ])
        ->shouldReceive('getCollectionsPermissionsData')
        ->andReturn(
            [
                'Booking' => [
                    'browse' => [
                        0 => 100,
                    ],
                ],
            ],
            [
                'Booking' => [
                    'browse' => [
                        0 => 100,
                    ],
                ],
            ],
        )
        ->getMock();
    invokeProperty($mockPermissions, 'caller', QueryStringParser::parseCaller($request));

    expect(static fn () => $mockPermissions->can('browse', $collection))
        ->toThrow(ForbiddenError::class, 'You don\'t have permission to browse this collection.');
});

test('canChart() should return true on allowed chart', function () {
    $post = [
        'aggregateFieldName'   => null,
        'aggregator'           => 'Count',
        'contextVariables'     => '{}',
        'filter'               => null,
        'sourceCollectionName' => 'Booking',
        'type'                 => 'Value',
    ];
    [$permissions, $collection] = permissionsFactory($post);
    $request = Request::createFromGlobals();

    expect($permissions->canChart($request))->toBeTrue();
});

test('canChart() should call twice and return true on allowed chart', function () {
    $post = [
        'aggregator'           => 'Count',
        'groupByFieldName'     => 'id',
        'sourceCollectionName' => 'Booking',
        'type'                 => 'Pie',
    ];
    permissionsFactory($post);
    $request = Request::createFromGlobals();
    $mockPermissions = mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getChartData')
        ->andReturn(
            [],
            [$post['type'] . ':' . sha1(json_encode($post, JSON_THROW_ON_ERROR))]
        )
        ->getMock();

    $caller = QueryStringParser::parseCaller($request);
    invokeProperty($caller, 'permissionLevel', 'foo');
    invokeProperty($mockPermissions, 'caller', $caller);

    expect($mockPermissions->canChart($request))->toBeTrue();
});

test('canChart() should throw on forbidden chart', function () {
    $post = [
        'aggregator'           => 'Count',
        'groupByFieldName'     => 'registrationNumber',
        'sourceCollectionName' => 'Car',
        'type'                 => 'Pie',
    ];
    permissionsFactory($post);
    $request = Request::createFromGlobals();
    $mockPermissions = mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getChartData')
        ->andReturn(
            [],
        )
        ->getMock();

    $caller = QueryStringParser::parseCaller($request);
    invokeProperty($caller, 'permissionLevel', 'foo');
    invokeProperty($mockPermissions, 'caller', $caller);

    expect(static fn () => $mockPermissions->canChart($request))
        ->toThrow(ForbiddenError::class, 'You don\'t have permission to access this collection.');
});

//test('canSmartAction() ', function () {
//    [$permissions, $collection] = permissionsFactory();
//
//    $request = Request::createFromGlobals();
//    $mockPermissions = mock(Permissions::class)
//        ->makePartial()
//        ->shouldAllowMockingProtectedMethods()
//        ->shouldReceive('hasPermissionSystem')
//        ->andReturnTrue()
//        ->getMock();
////
////    $caller = QueryStringParser::parseCaller($request);
////    invokeProperty($caller, 'permissionLevel', 'foo');
////    invokeProperty($mockPermissions, 'caller', $caller);
////
////    expect(static fn () => $mockPermissions->canChart($request))
////        ->toThrow(ForbiddenError::class, 'You don\'t have permission to access this collection.');
//});


//
//test('getScope() should return null when permission has no scopes', function () {
//    $permissions = permissionsFactory();
//    $booking = new Collection(new Datasource(), 'Booking');
//
//    expect($permissions->getScope($booking))->toBeNull();
//});
//
//test('getScope() should work in simple case', function () {
//    $permissions = permissionsFactory(
//        [
//            'Booking' => [
//                'conditionTree'      => new ConditionTreeLeaf('id', Operators::EQUAL, 43),
//                'dynamicScopeValues' => [],
//            ],
//        ]
//    );
//
//    $conditionTree = $permissions->getScope(AgentFactory::get('datasource')->getCollection('Booking'));
//
//    expect($conditionTree)->toEqual(new ConditionTreeLeaf('id', Operators::EQUAL, 43));
//});
//
//test('getScope() should work with substitutions', function () {
//    $permissions = permissionsFactory(
//        [
//            'Booking' => [
//                'conditionTree'      => new ConditionTreeLeaf('id', Operators::EQUAL, '$currentUser.tags.something'),
//                'dynamicScopeValues' => [1 => ['$currentUser.tags.something' => 'dynamicValue']],
//            ],
//        ]
//    );
//
//    $conditionTree = $permissions->getScope(AgentFactory::get('datasource')->getCollection('Booking'));
//
//    expect($conditionTree)->toEqual(new ConditionTreeLeaf('id', Operators::EQUAL, 'dynamicValue'));
//});
//
//test('getScope() should fallback to jwt when the cache is broken', function () {
//    $permissions = permissionsFactory(
//        [
//            'Booking' => [
//                'conditionTree'      => new ConditionTreeLeaf('id', Operators::EQUAL, '$currentUser.id'),
//                'dynamicScopeValues' => [],
//            ],
//        ]
//    );
//
//    $conditionTree = $permissions->getScope(AgentFactory::get('datasource')->getCollection('Booking'));
//
//    expect($conditionTree)->toEqual(new ConditionTreeLeaf('id', Operators::EQUAL, 1));
//});
//
//test('getScope() should fallback to jwt when cache broken for tags', function () {
//    $permissions = permissionsFactory(
//        [
//            'Booking' => [
//                'conditionTree'      => new ConditionTreeLeaf('id', Operators::EQUAL, '$currentUser.tags.something'),
//                'dynamicScopeValues' => [],
//            ],
//        ]
//    );
//
//    $conditionTree = $permissions->getScope(AgentFactory::get('datasource')->getCollection('Booking'));
//
//    expect($conditionTree)->toEqual(new ConditionTreeLeaf('id', Operators::EQUAL, 'tagValue'));
//});
//
//test('getRenderingPermissions() should call fetch permissions if the cache is invalid', function () {
//    $chart = ['type' => 'Value', 'filter' => null, 'aggregator' => 'Count', 'aggregateFieldName' => null, 'sourceCollectionId' => 'Car'];
//    $prophet = new Prophet();
//    $forestApi = $prophet->prophesize(ForestApiRequester::class);
//    $forestApi
//        ->get(Argument::type('string'), Argument::type('array'))
//        ->shouldBeCalled()
//        ->willReturn(
//            new Response(200, [], json_encode(
//                [
//                    'data'  => [
//                        'collections' =>
//                            [
//                                'Booking' => [
//                                    'collection' => ['browseEnabled' => [1], 'readEnabled' => [1], 'editEnabled' => [1], 'addEnabled' => [1], 'deleteEnabled' => [1], 'exportEnabled' => [1]],
//                                    'actions'    => ['mySmartAction' => ['triggerEnabled' => [1]]],
//                                ],
//                            ],
//                        'renderings'  =>
//                            [
//                                10 => [
//                                    'Car' => [
//                                        'scope'    => [
//                                            'filter'              => [
//                                                'aggregator' => 'and',
//                                                'conditions' => [['field' => 'brand', 'operator' => 'contains', 'value' => 'et']],
//                                            ],
//                                            'dynamicScopesValues' => [],
//                                        ],
//                                        'segments' => [],
//                                    ],
//                                ],
//                            ],
//                    ],
//                    'stats' => [
//                        'queries'      => [],
//                        'leaderboards' => [],
//                        'lines'        => [],
//                        'objectives'   => [],
//                        'percentages'  => [],
//                        'pies'         => [],
//                        'values'       => [$chart],
//                    ],
//                ],
//                JSON_THROW_ON_ERROR
//            ))
//        );
//
//    $permissions = permissionsFactory();
//    invokeProperty($permissions, 'forestApi', $forestApi->reveal());
//    $permissions->invalidateCache(10);
//
//    expect(invokeMethod($permissions, 'getRenderingPermissions', [10]))
//        ->toEqual(
//            collect(
//                [
//                    'actions' => collect(
//                        [
//                            'browse:Booking'               => collect([1]),
//                            'read:Booking'                 => collect([1]),
//                            'edit:Booking'                 => collect([1]),
//                            'add:Booking'                  => collect([1]),
//                            'delete:Booking'               => collect([1]),
//                            'export:Booking'               => collect([1]),
//                            'custom:mySmartAction:Booking' => collect([1]),
//                        ]
//                    ),
//                    'scopes'  => collect(
//                        [
//                            'Car' => ['conditionTree' => new ConditionTreeLeaf('brand', Operators::CONTAINS, 'et'), 'dynamicScopeValues' => []],
//                        ]
//                    ),
//                    'charts'  => collect([strtolower(Str::plural($chart['type'])) . ':' . sha1(json_encode(ksort($chart), JSON_THROW_ON_ERROR))]),
//                ]
//            )
//        );
//});
//
//test('getRenderingPermissions() should throw when ACL not activated', function () {
//    $prophet = new Prophet();
//    $forestApi = $prophet->prophesize(ForestApiRequester::class);
//    $forestApi
//        ->get(Argument::type('string'), Argument::type('array'))
//        ->shouldBeCalled()
//        ->willReturn(
//            new Response(200, [], json_encode(
//                [
//                    'data'  => [],
//                    'stats' => [],
//                    'meta'  => ['rolesACLActivated' => false],
//                ],
//                JSON_THROW_ON_ERROR
//            ))
//        );
//
//    $permissions = permissionsFactory();
//    invokeProperty($permissions, 'forestApi', $forestApi->reveal());
//    $permissions->invalidateCache(10);
//
//    expect(static fn () => invokeMethod($permissions, 'getRenderingPermissions', [10]))
//        ->toThrow(ForestException::class, '🌳🌳🌳 Roles V2 are unsupported');
//});
