<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Listing;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

function factoryListing($args = []): Listing
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'Europe/Paris';

    $collectionUsers = new Collection($datasource, 'User');
    $collectionUsers->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'birthday'   => new ColumnSchema(columnType: PrimitiveType::DATE),
            'active'     => new ColumnSchema(columnType: PrimitiveType::BOOLEAN),
        ]
    );

    if (isset($args['listing'])) {
        $collectionUsers = mock($collectionUsers)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn($args['listing'])
            ->getMock();
    }

    if (isset($args['export'])) {
        $collectionUsers = mock($collectionUsers)
            ->shouldReceive('export')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn($args['export'])
            ->getMock();
    }

    $datasource->addCollection($collectionUsers);

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
                        'browse:User' => collect([1]),
                        'export:User' => collect([1]),
                    ]
                ),
                'scopes'  => collect(),
            ]
        ),
        300
    );

    $listing = mock(Listing::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->andReturnNull()
        ->getMock();

    invokeProperty($listing, 'request', $request);

    return $listing;
}

test('addRoute() should return a new route', function () {
    $listing = new Listing();
    $listing->addRoute('foo', ['POST', 'GET'], '/route/hello-world', fn () => 'hello world');

    expect($listing->getRoutes())->toBeArray()
        ->and($listing->getRoutes()['foo']['methods'])->toEqual(['POST', 'GET'])
        ->and($listing->getRoutes()['foo']['uri'])->toEqual('/route/hello-world')
        ->and($listing->getRoutes()['foo']['closure']())->toEqual('hello world');
});

test('make() should return a new instance of Listing with routes', function () {
    $listing = Listing::make();

    expect($listing)->toBeInstanceOf(Listing::class)
        ->and($listing->getRoutes())->toHaveKey('forest.list');
});

test('handleRequest() should return a response 200', function () {
    $data = [
        [
            'id'         => 1,
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'birthday'   => '1980-01-01',
            'active'     => true,
        ],
        [
            'id'         => 2,
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'birthday'   => '1984-01-01',
            'active'     => true,
        ],
    ];
    $listing = factoryListing(['listing' => $data]);

    expect($listing->handleRequest(['collectionName' => 'User']))
        ->toBeArray()
        ->toEqual(
            [
                'renderTransformer' => true,
                'name'              => 'User',
                'content'           => $data,
            ]
        );
});

test('handleRequestCsv() should return a response 200', function () {
    $_GET['filename'] = 'export-users';
    $_GET['header'] = 'id,first_name,last_name,birthday,active';
    $data = [
        [
            'id'         => 1,
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'birthday'   => '1980-01-01',
            'active'     => true,
        ],
        [
            'id'         => 2,
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'birthday'   => '1984-01-01',
            'active'     => true,
        ],
    ];

    $listing = factoryListing(['export' => $data]);

    expect($listing->handleRequest(['collectionName' => 'User.csv']))
        ->toBeArray()
        ->toEqual(
            [
                'content' => "id,first_name,last_name,birthday,active\n1,John,Doe,1980-01-01,1\n2,Jane,Doe,1984-01-01,1\n",
                'headers' => [
                    'Content-type'        => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $_GET['filename'] . '.csv"',
                ],
            ]
        );
});
