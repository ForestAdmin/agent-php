<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

function permissionsFactory($scopes = [], $post = []): Permissions
{
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJqb2huLmRvZUBkb21haW4uY29tIiwiZmlyc3ROYW1lIjoiSm9obiIsImxhc3ROYW1lIjoiRG9lIiwidGVhbSI6IkRldmVsb3BlcnMiLCJyZW5kZXJpbmdJZCI6IjEwIiwidGFncyI6eyJzb21ldGhpbmciOiJ0YWdWYWx1ZSJ9LCJ0aW1lem9uZSI6IkV1cm9wZS9QYXJpcyJ9.iNiTlSoaCfUIOJ643E8AdhbsmIu45KB4L-TaCt0qNyU';
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
        'envSecret'    => '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d',
        'isProduction' => false,
    ];
    (new AgentFactory($options))->addDatasources([$datasource]);

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
