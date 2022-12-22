<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Prophecy\Argument;
use Prophecy\Prophet;
use Symfony\Component\HttpKernel\Exception\HttpException;

function permissionsFactory($scopes = [], $post = []): Permissions
{
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET = ['timezone' => 'Europe/Paris'];
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
    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    $request = Request::createFromGlobals();
    $permissions = new Permissions(QueryStringParser::parseCaller($request));
    Cache::put(
        $permissions->getCacheKey(10),
        collect(
            [
                'actions' => collect(
                    [
                        'browse:Booking' => collect([1]),
                        'read:Booking'   => collect([1]),
                        'edit:Booking'   => collect([1]),
                        'add:Booking'    => collect([1]),
                        'delete:Booking' => collect([1]),
                    ]
                ),
                'scopes'  => collect($scopes),
                'charts'  => empty($post) ? collect($post) : collect(
                    [
                        strtolower(Str::plural($post['type'])) . ':' . sha1(json_encode(ksort($post), JSON_THROW_ON_ERROR)),
                    ]
                ),
            ]
        ),
        300
    );

    return $permissions;
}

test('getCacheKey() should return the right key', function () {
    $permissions = permissionsFactory();

    expect($permissions->getCacheKey(10))->toEqual('permissions.10');
});

test('invalidate cache should delete the cache', function () {
    $permissions = permissionsFactory();
    $permissions->invalidateCache(10);

    expect(Cache::get($permissions->getCacheKey(10)))->toBeNull();
});

test('can() should call getRenderingPermissions twice', function () {
    permissionsFactory();
    $request = Request::createFromGlobals();
    $mockPermissions = mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getRenderingPermissions')
        ->with(10)
        ->andReturn(
            collect(
                [
                    'actions' => collect(
                        [
                            'browse:Book' => collect([1]),
                        ]
                    ),
                ]
            ),
            collect(
                [
                    'actions' => collect(
                        [
                            'browse:Booking' => collect([1]),
                        ]
                    ),
                ]
            )
        )
        ->getMock();
    invokeProperty($mockPermissions, 'caller', QueryStringParser::parseCaller($request));

    expect($mockPermissions->can('browse:Booking', 'Booking'))->toBeTrue();
});

test('can() should return true', function () {
    $permissions = permissionsFactory();

    expect($permissions->can('browse:Booking', 'Booking'))->toBeTrue();
});

test('can() should throw HttpException', function () {
    $permissions = permissionsFactory();

    expect(static fn () => $permissions->can('browse:Book', false))
        ->toThrow(HttpException::class, 'Forbidden');
});

test('canChart() should return true on allowed chart', function () {
    $permissions = permissionsFactory(
        [],
        [
            'type'           => 'Pie',
            'aggregate'      => 'Count',
            'collection'     => 'books',
            'group_by_field' => 'author:firstName',
        ]
    );
    $request = Request::createFromGlobals();

    expect($permissions->canChart($request, false))->toBeTrue();
});

test('canChart() should call twice and return true on allowed chart', function () {
    $post = [
        'type'           => 'Pie',
        'aggregate'      => 'Count',
        'collection'     => 'books',
        'group_by_field' => 'author:firstName',
    ];
    permissionsFactory([], $post);
    $request = Request::createFromGlobals();
    $mockPermissions = mock(Permissions::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('getRenderingPermissions')
        ->with(10)
        ->andReturn(
            collect(['charts' => collect($post)]),
            collect(['charts' => collect([strtolower(Str::plural($post['type'])) . ':' . sha1(json_encode(ksort($post), JSON_THROW_ON_ERROR))])])
        )
        ->getMock();
    $mockPermissions->invalidateCache(10);
    $caller = QueryStringParser::parseCaller($request);
    invokeProperty($caller, 'permissionLevel', 'foo');
    invokeProperty($mockPermissions, 'caller', $caller);

    expect($mockPermissions->canChart($request))->toBeTrue();
});

test('canChart() should throw on forbidden chart', function () {
    $permissions = permissionsFactory(
        [],
        [
            'type'           => 'Pie',
            'aggregate'      => 'Count',
            'collection'     => 'books',
            'group_by_field' => 'author:firstName',
        ]
    );
    $_POST['type'] = 'Value';
    $request = Request::createFromGlobals();

    expect(static fn () => $permissions->canChart($request, false))
        ->toThrow(HttpException::class, 'Forbidden');
});

test('getScope() should return null when permission has no scopes', function () {
    $permissions = permissionsFactory();
    $booking = new Collection(new Datasource(), 'Booking');

    expect($permissions->getScope($booking))->toBeNull();
});

test('getScope() should work in simple case', function () {
    $permissions = permissionsFactory(
        [
            'Booking' => [
                'conditionTree'      => new ConditionTreeLeaf('id', Operators::EQUAL, 43),
                'dynamicScopeValues' => [],
            ],
        ]
    );

    $conditionTree = $permissions->getScope(AgentFactory::get('datasource')->getCollection('Booking'));

    expect($conditionTree)->toEqual(new ConditionTreeLeaf('id', Operators::EQUAL, 43));
});

test('getScope() should work with substitutions', function () {
    $permissions = permissionsFactory(
        [
            'Booking' => [
                'conditionTree'      => new ConditionTreeLeaf('id', Operators::EQUAL, '$currentUser.tags.something'),
                'dynamicScopeValues' => [1 => ['$currentUser.tags.something' => 'dynamicValue']],
            ],
        ]
    );

    $conditionTree = $permissions->getScope(AgentFactory::get('datasource')->getCollection('Booking'));

    expect($conditionTree)->toEqual(new ConditionTreeLeaf('id', Operators::EQUAL, 'dynamicValue'));
});

test('getScope() should fallback to jwt when the cache is broken', function () {
    $permissions = permissionsFactory(
        [
            'Booking' => [
                'conditionTree'      => new ConditionTreeLeaf('id', Operators::EQUAL, '$currentUser.id'),
                'dynamicScopeValues' => [],
            ],
        ]
    );

    $conditionTree = $permissions->getScope(AgentFactory::get('datasource')->getCollection('Booking'));

    expect($conditionTree)->toEqual(new ConditionTreeLeaf('id', Operators::EQUAL, 1));
});

test('getScope() should fallback to jwt when cache broken for tags', function () {
    $permissions = permissionsFactory(
        [
            'Booking' => [
                'conditionTree'      => new ConditionTreeLeaf('id', Operators::EQUAL, '$currentUser.tags.something'),
                'dynamicScopeValues' => [],
            ],
        ]
    );

    $conditionTree = $permissions->getScope(AgentFactory::get('datasource')->getCollection('Booking'));

    expect($conditionTree)->toEqual(new ConditionTreeLeaf('id', Operators::EQUAL, 'tagValue'));
});

test('getRenderingPermissions() should call fetch permissions if the cache is invalid', function () {
    $chart = ['type' => 'Value', 'filter' => null, 'aggregator' => 'Count', 'aggregateFieldName' => null, 'sourceCollectionId' => 'Car'];
    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
        ->get(Argument::type('string'), Argument::type('array'))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode(
                [
                    'data'  => [
                        'collections' =>
                            [
                                'Booking' => [
                                    'collection' => ['browseEnabled' => [1], 'readEnabled' => [1], 'editEnabled' => [1], 'addEnabled' => [1], 'deleteEnabled' => [1], 'exportEnabled' => [1]],
                                    'actions'    => ['mySmartAction' => ['triggerEnabled' => [1]]],
                                ],
                            ],
                        'renderings'  =>
                            [
                                10 => [
                                    'Car' => [
                                        'scope'    => [
                                            'filter'              => [
                                                'aggregator' => 'and',
                                                'conditions' => [['field' => 'brand', 'operator' => 'contains', 'value' => 'et']],
                                            ],
                                            'dynamicScopesValues' => [],
                                        ],
                                        'segments' => [],
                                    ],
                                ],
                            ],
                    ],
                    'stats' => [
                        'queries'      => [],
                        'leaderboards' => [],
                        'lines'        => [],
                        'objectives'   => [],
                        'percentages'  => [],
                        'pies'         => [],
                        'values'       => [$chart],
                    ],
                ],
                JSON_THROW_ON_ERROR
            ))
        );

    $permissions = permissionsFactory();
    invokeProperty($permissions, 'forestApi', $forestApi->reveal());
    $permissions->invalidateCache(10);

    expect(invokeMethod($permissions, 'getRenderingPermissions', [10]))
        ->toEqual(
            collect(
                [
                    'actions' => collect(
                        [
                            'browse:Booking'               => collect([1]),
                            'read:Booking'                 => collect([1]),
                            'edit:Booking'                 => collect([1]),
                            'add:Booking'                  => collect([1]),
                            'delete:Booking'               => collect([1]),
                            'export:Booking'               => collect([1]),
                            'custom:mySmartAction:Booking' => collect([1]),
                        ]
                    ),
                    'scopes'  => collect(
                        [
                            'Car' => ['conditionTree' => new ConditionTreeLeaf('brand', Operators::CONTAINS, 'et'), 'dynamicScopeValues' => []],
                        ]
                    ),
                    'charts'  => collect([strtolower(Str::plural($chart['type'])) . ':' . sha1(json_encode(ksort($chart), JSON_THROW_ON_ERROR))]),
                ]
            )
        );
});

test('getRenderingPermissions() should throw when ACL not activated', function () {
    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
        ->get(Argument::type('string'), Argument::type('array'))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode(
                [
                    'data'  => [],
                    'stats' => [],
                    'meta'  => ['rolesACLActivated' => false],
                ],
                JSON_THROW_ON_ERROR
            ))
        );

    $permissions = permissionsFactory();
    invokeProperty($permissions, 'forestApi', $forestApi->reveal());
    $permissions->invalidateCache(10);

    expect(static fn () => invokeMethod($permissions, 'getRenderingPermissions', [10]))
        ->toThrow(ForestException::class, '🌳🌳🌳 Roles V2 are unsupported');
});
