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
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

use GuzzleHttp\Psr7\Response;

use Prophecy\Prophet;

function permissionsFactory(TestCase $testCase, array $post = [], $scope = null, $loadDataSetForestSchema = true)
{
    if ($loadDataSetForestSchema) {
        $options = AGENT_OPTIONS;
        $options['schemaPath'] = 'tests/Datasets/.forestadmin-schema.json';
        new AgentFactory($options, []);
    }

    $_POST = $post;
    $roleId = 1;
    $userRoleId = 1;

    $datasource = new Datasource();
    $collectionBooking = new Collection($datasource, 'Booking');
    $collectionBooking->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $datasource->addCollection($collectionBooking);
    $testCase->buildAgent($datasource);

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
                                    'roles' => [],
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
                        'scope'             => $scope,
                        'segments'          => [
                            'SELECT id FROM bookings WHERE title IS NOT NULL;',
                        ],
                        'liveQuerySegments' => [
                            [
                                'query'          => 'SELECT id FROM bookings WHERE title IS NOT NULL;',
                                'connectionName' => 'EloquentDatasource',
                            ],
                        ],
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
    $testCase->invokeProperty($permissions, 'forestApi', $forestApi->reveal());

    $testCase->bucket['permissions'] = $permissions;
    $testCase->bucket['collection'] = $collectionBooking;
}

test('invalidate cache should delete the cache', function () {
    permissionsFactory($this);
    $permissions = $this->bucket['permissions'];
    $permissions->invalidateCache('forest.stats');

    expect(Cache::get('forest.stats'))->toBeNull();
});

test('can() should return true when user is allowed', function () {
    permissionsFactory($this);
    $permissions = $this->bucket['permissions'];
    $collection = $this->bucket['collection'];

    expect($permissions->can('browse', $collection))->toBeTrue();
});

test('can() should call getCollectionsPermissionsData twice', function () {
    permissionsFactory($this);
    $collection = $this->bucket['collection'];
    $request = Request::createFromGlobals();
    $mockPermissions = \Mockery::mock(Permissions::class)
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
        ->shouldReceive('fetch')
        ->andReturn(
            [
                'collections' => [
                    'Booking' => [
                        'collection' => [
                            'browseEnabled' => [
                                'roles' => [1000],
                            ],
                            'readEnabled'   => [
                                'roles' => [1000],
                            ],
                            'editEnabled'   => [
                                'roles' => [1000],
                            ],
                            'addEnabled'    => [
                                'roles' => [1000],
                            ],
                            'deleteEnabled' => [
                                'roles' => [1000],
                            ],
                            'exportEnabled' => [
                                'roles' => [1000],
                            ],
                        ],
                        'actions'    => [],
                    ],
                ],
            ],
            [
                'collections' => [
                    'Booking' => [
                        'collection' => [
                            'browseEnabled' => [
                                'roles' => [1],
                            ],
                            'readEnabled'   => [
                                'roles' => [1],
                            ],
                            'editEnabled'   => [
                                'roles' => [1],
                            ],
                            'addEnabled'    => [
                                'roles' => [1],
                            ],
                            'deleteEnabled' => [
                                'roles' => [1],
                            ],
                            'exportEnabled' => [
                                'roles' => [1],
                            ],
                        ],
                        'actions'    => [],
                    ],
                ],
            ]
        )
        ->getMock();
    $this->invokeProperty($mockPermissions, 'caller', QueryStringParser::parseCaller($request));

    expect($mockPermissions->can('browse', $collection))->toBeTrue();
});

test('can() should throw HttpException when user doesn\'t have the right access', function () {
    permissionsFactory($this);
    $collection = $this->bucket['collection'];
    $request = Request::createFromGlobals();
    $mockPermissions = \Mockery::mock(Permissions::class)
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
        ->shouldReceive('fetch')
        ->andReturn(
            [
                'collections' => [
                    'Booking' => [
                        'collection' => [
                            'browseEnabled' => [
                                'roles' => [1000],
                            ],
                            'readEnabled'   => [
                                'roles' => [1000],
                            ],
                            'editEnabled'   => [
                                'roles' => [1000],
                            ],
                            'addEnabled'    => [
                                'roles' => [1000],
                            ],
                            'deleteEnabled' => [
                                'roles' => [1000],
                            ],
                            'exportEnabled' => [
                                'roles' => [1000],
                            ],
                        ],
                        'actions'    => [],
                    ],
                ],
            ],
            [
                'collections' => [
                    'Booking' => [
                        'collection' => [
                            'browseEnabled' => [
                                'roles' => [1000],
                            ],
                            'readEnabled'   => [
                                'roles' => [1000],
                            ],
                            'editEnabled'   => [
                                'roles' => [1000],
                            ],
                            'addEnabled'    => [
                                'roles' => [1000],
                            ],
                            'deleteEnabled' => [
                                'roles' => [1000],
                            ],
                            'exportEnabled' => [
                                'roles' => [1000],
                            ],
                        ],
                        'actions'    => [],
                    ],
                ],
            ]
        )
        ->getMock();
    $this->invokeProperty($mockPermissions, 'caller', QueryStringParser::parseCaller($request));

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
    permissionsFactory($this, $post);
    $permissions = $this->bucket['permissions'];
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
    permissionsFactory($this, $post);
    $request = Request::createFromGlobals();
    $mockPermissions = \Mockery::mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('fetch')
        ->andReturn(
            [
                'collections' => [
                    'Booking' => [
                        'scope'             => null,
                        'segments'          => [],
                        'liveQuerySegments' => [],
                    ],
                ],
                'stats' => [],
            ],
            [
                'collections' => [
                    'Booking' => [
                        'scope'             => null,
                        'segments'          => [],
                        'liveQuerySegments' => [],
                    ],
                ],
                'stats' => [
                    [
                        'type'                 => 'Pie',
                        'filter'               => null,
                        'aggregator'           => 'Count',
                        'groupByFieldName'     => 'id',
                        'aggregateFieldName'   => null,
                        'sourceCollectionName' => 'Booking',
                    ],
                ],
            ]
        )
        ->getMock();

    $caller = QueryStringParser::parseCaller($request);
    $this->invokeProperty($caller, 'permissionLevel', 'foo');
    $this->invokeProperty($mockPermissions, 'caller', $caller);

    expect($mockPermissions->canChart($request))->toBeTrue();
});

test('canChart() should throw on forbidden chart', function () {
    $post = [
        'aggregator'           => 'Count',
        'groupByFieldName'     => 'registrationNumber',
        'sourceCollectionName' => 'Car',
        'type'                 => 'Pie',
    ];
    permissionsFactory($this, $post);
    $request = Request::createFromGlobals();
    $mockPermissions = \Mockery::mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getChartData')
        ->andReturn(
            [],
        )
        ->getMock();

    $caller = QueryStringParser::parseCaller($request);
    $this->invokeProperty($caller, 'permissionLevel', 'foo');
    $this->invokeProperty($mockPermissions, 'caller', $caller);

    expect(static fn () => $mockPermissions->canChart($request))
        ->toThrow(ForbiddenError::class, 'You don\'t have permission to access this collection.');
});

test('getScope() should return null when permission has no scopes', function () {
    permissionsFactory($this);
    $permissions = $this->bucket['permissions'];
    $fakeCollection = new Collection(new Datasource(), 'FakeCollection');

    expect($permissions->getScope($fakeCollection))->toBeNull();
});

test('getScope() should work in simple case', function () {
    $scope = [
        'aggregator' => 'and',
        'conditions' => [
            [
                'field'    => 'id',
                'operator' => 'greater_than',
                'value'    => '1',
            ],
            [
                'field'    => 'title',
                'operator' => 'present',
                'value'    => null,
            ],
        ],
    ];
    permissionsFactory($this, [], $scope);
    $permissions = $this->bucket['permissions'];
    $collection = $this->bucket['collection'];
    $conditionTree = $permissions->getScope($collection);

    expect($conditionTree)->toEqual(ConditionTreeFactory::fromArray($scope));
});

test('getScope() should work with substitutions', function () {
    $scope = [
        'aggregator' => 'and',
        'conditions' => [
            [
                'field'    => 'id',
                'operator' => 'equal',
                'value'    => '{{currentUser.id}}',
            ],
        ],
    ];
    permissionsFactory($this, [], $scope);
    $permissions = $this->bucket['permissions'];
    $collection = $this->bucket['collection'];
    $conditionTree = $permissions->getScope($collection);

    expect($conditionTree)->toEqual(ConditionTreeFactory::fromArray([
        'aggregator' => 'and',
        'conditions' => [
            [
                'field'    => 'id',
                'operator' => 'equal',
                'value'    => '1',
            ],
        ],
    ]));
});

test('canExecuteQuerySegment() should return true on allowed segment', function () {
    permissionsFactory($this);
    $permissions = $this->bucket['permissions'];
    $collection = $this->bucket['collection'];
    $query = 'SELECT id FROM bookings WHERE title IS NOT NULL;';
    $connectionName = 'EloquentDatasource';

    expect($permissions->canExecuteQuerySegment($collection, $query, $connectionName))->toBeTrue();
});

test('canExecuteQuerySegment() should throw on forbidden segment', function () {
    permissionsFactory($this);
    $permissions = $this->bucket['permissions'];
    $collection = $this->bucket['collection'];
    $query = 'SELECT id FROM bookings;';
    $connectionName = 'EloquentDatasource';

    expect(static fn () => $permissions->canExecuteQuerySegment($collection, $query, $connectionName))
        ->toThrow(ForbiddenError::class, 'You don\'t have permission to use this query segment.');
});

test('canExecuteQuerySegment() should call twice and return true on allowed segment', function () {
    $post = [];
    permissionsFactory($this, $post);
    $request = Request::createFromGlobals();
    $collection = $this->bucket['collection'];
    $query = 'SELECT id FROM bookings WHERE title IS NOT NULL;';
    $connectionName = 'EloquentDatasource';

    $mockPermissions = \Mockery::mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('fetch')
        ->andReturn(
            [
                'collections' => [
                    'Booking' => [
                        'scope'             => null,
                        'segments'          => [],
                        'liveQuerySegments' => [],
                    ],
                ],
                'stats' => [],
            ],
            [
                'collections' => [
                    'Booking' => [
                        'scope'             => null,
                        'segments'          => [],
                        'liveQuerySegments' => [
                            [
                                'query'          => $query,
                                'connectionName' => 'EloquentDatasource',
                            ],
                        ],
                    ],
                ],
                'stats' => [],
            ]
        )
        ->getMock();

    $caller = QueryStringParser::parseCaller($request);
    $this->invokeProperty($mockPermissions, 'caller', $caller);

    expect($mockPermissions->canExecuteQuerySegment($collection, $query, $connectionName))->toBeTrue();
});

test('canSmartAction() should return true when user can execute the action', function () {
    $post = [
        'data' => [
            'attributes' => [
                'values'                   => [],
                'ids'                      => [1],
                'collection_name'          => 'Booking',
                'parent_collection_name'   => null,
                'parent_collection_id'     => null,
                'parent_association_name'  => null,
                'all_records'              => false,
                'all_records_subset_query' => [
                    'fields[Booking]'   => 'id,title',
                    'page[number]'      => 1,
                    'page[size]'        => 15,
                    'sort'              => '-id',
                    'timezone'          => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'Booking-Mark@@@as@@@live',
                'signed_approval_request'  => null,
            ],
            'type'       => 'custom-action-requests',
        ],
    ];
    permissionsFactory($this, $post);
    $permissions = $this->bucket['permissions'];
    $collection = \Mockery::mock($this->bucket['collection'])
        ->shouldReceive('aggregate')
        ->andReturn([['value' => 1]])
        ->getMock();

    $request = Request::createFromGlobals();
    $mockRequest = \Mockery::mock($request)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getPathInfo')
        ->andReturn('/forest/_actions/Booking/0/mark-as-live')
        ->shouldReceive('getMethod')
        ->andReturn('POST')
        ->getMock();

    expect($permissions->canSmartAction($mockRequest, $collection, new Filter()))->toBeTrue();
});

test('canSmartAction() should return true when the permissions system is deactivate', function () {
    $post = [
        'data' => [
            'attributes' => [
                'values'                   => [],
                'ids'                      => [1],
                'collection_name'          => 'Booking',
                'parent_collection_name'   => null,
                'parent_collection_id'     => null,
                'parent_association_name'  => null,
                'all_records'              => false,
                'all_records_subset_query' => [
                    'fields[Booking]'   => 'id,title',
                    'page[number]'      => 1,
                    'page[size]'        => 15,
                    'sort'              => '-id',
                    'timezone'          => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'Booking-Mark@@@as@@@live',
                'signed_approval_request'  => null,
            ],
            'type'       => 'custom-action-requests',
        ],
    ];
    permissionsFactory($this, $post);
    $permissions = $this->bucket['permissions'];
    $collection = $this->bucket['collection'];
    Cache::put('forest.has_permission', false, config('permissionExpiration'));

    $request = Request::createFromGlobals();
    $mockRequest = \Mockery::mock($request)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getPathInfo')
        ->andReturn('/forest/_actions/Booking/0/mark-as-live')
        ->shouldReceive('getMethod')
        ->andReturn('POST')
        ->getMock();

    expect($permissions->canSmartAction($mockRequest, $collection, new Filter()))->toBeTrue();
});

test('canSmartAction() should throw when the action is unknown', function () {
    $post = [
        'data' => [
            'attributes' => [
                'values'                   => [],
                'ids'                      => [1],
                'collection_name'          => 'FakeCollection',
                'parent_collection_name'   => null,
                'parent_collection_id'     => null,
                'parent_association_name'  => null,
                'all_records'              => false,
                'all_records_subset_query' => [
                    'fields[Booking]'   => 'id,title',
                    'page[number]'      => 1,
                    'page[size]'        => 15,
                    'sort'              => '-id',
                    'timezone'          => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'FakeCollection-fake-smart-action',
                'signed_approval_request'  => null,
            ],
            'type'       => 'custom-action-requests',
        ],
    ];
    permissionsFactory($this, $post);
    $permissions = $this->bucket['permissions'];
    $collection = $this->bucket['collection'];

    $request = Request::createFromGlobals();
    $mockRequest = \Mockery::mock($request)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getPathInfo')
        ->andReturn('/forest/_actions/FakeCollection/0/fake-smart-action')
        ->shouldReceive('getMethod')
        ->andReturn('POST')
        ->getMock();

    expect(fn () => $permissions->canSmartAction($mockRequest, $collection, new Filter()))->toThrow(ForestException::class, '🌳🌳🌳 The collection Booking does not have this smart action');
});

test('canSmartAction() should throw when the forest schema doesn\'t have any actions', function () {
    $post = [
        'data' => [
            'attributes' => [
                'values'                   => [],
                'ids'                      => [1],
                'collection_name'          => 'FakeCollection',
                'parent_collection_name'   => null,
                'parent_collection_id'     => null,
                'parent_association_name'  => null,
                'all_records'              => false,
                'all_records_subset_query' => [
                    'fields[Booking]'   => 'id,title',
                    'page[number]'      => 1,
                    'page[size]'        => 15,
                    'sort'              => '-id',
                    'timezone'          => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'FakeCollection-fake-smart-action',
                'signed_approval_request'  => null,
            ],
            'type'       => 'custom-action-requests',
        ],
    ];
    permissionsFactory($this, $post, null, true);
    $permissions = $this->bucket['permissions'];
    $collection = $this->bucket['collection'];

    $request = Request::createFromGlobals();
    $mockRequest = \Mockery::mock($request)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getPathInfo')
        ->andReturn('/forest/_actions/FakeCollection/0/fake-smart-action')
        ->shouldReceive('getMethod')
        ->andReturn('POST')
        ->getMock();

    expect(fn () => $permissions->canSmartAction($mockRequest, $collection, new Filter()))->toThrow(ForestException::class, '🌳🌳🌳 The collection Booking does not have this smart action');
});
