<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Symfony\Component\HttpKernel\Exception\HttpException;

dataset('permissions', function () {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJqb2huLmRvZUBkb21haW4uY29tIiwiZmlyc3ROYW1lIjoiSm9obiIsImxhc3ROYW1lIjoiRG9lIiwidGVhbSI6IkRldmVsb3BlcnMiLCJyZW5kZXJpbmdJZCI6IjEwIiwidGFncyI6W10sInRpbWV6b25lIjoiRXVyb3BlL1BhcmlzIn0.-zTadg2QjQSH6b5kZxa4kSfBCZBAZq9T4ZJqAkTAQcs';
    $_GET = ['timezone' => 'Europe/Paris'];
    $datasource = new Datasource();
    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d',
        'isProduction' => false,
    ];
    (new AgentFactory($options))->addDatasources([$datasource]);

    $request = Request::createFromGlobals();
    yield $permissions = new Permissions(QueryStringParser::parseCaller($request));
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
            ]
        ),
        300
    );
});

test('getCacheKey() should return the right key', function (Permissions $permissions) {
    expect($permissions->getCacheKey(10))->toEqual('permissions.10');
})->with('permissions');


test('invalidate cache should delete the cache', function (Permissions $permissions) {
    $permissions->invalidateCache(10);

    expect(Cache::get($permissions->getCacheKey(10)))->toBeNull();
})->with('permissions');

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

test('can() should return true', function (Permissions $permissions) {
    expect($permissions->can('browse:Booking', 'Booking'))->toBeTrue();
})->with('permissions');

test('can() should throw HttpException', function (Permissions $permissions) {
    expect(static fn () => $permissions->can('browse:Book', 'Booking', false))
        ->toThrow(HttpException::class, 'Forbidden');
})->with('permissions');
