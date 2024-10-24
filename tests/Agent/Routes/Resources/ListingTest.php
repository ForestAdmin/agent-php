<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Listing;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function ForestAdmin\config;

use GuzzleHttp\Psr7\Response;

use Prophecy\Argument;
use Prophecy\Prophet;
use Symfony\Component\HttpKernel\Exception\HttpException;

$before = static function (TestCase $testCase, $args = []) {
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->setSearchable(true);
    $collectionUser->addFields(
        [
            'id'             => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL], isPrimaryKey: true),
            'first_name'     => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'      => new ColumnSchema(columnType: PrimitiveType::STRING),
            'birthday'       => new ColumnSchema(columnType: PrimitiveType::DATE),
            'active'         => new ColumnSchema(columnType: PrimitiveType::BOOLEAN),
            'driver_licence' => new OneToOneSchema(
                originKey: 'driver_licence_id',
                originKeyTarget: 'id',
                foreignCollection: 'DriverLicence',
            ),
        ]
    );

    $collectionDriverLicence = new Collection($datasource, 'DriverLicence');
    $collectionDriverLicence->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'reference ' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
        ]
    );

    if (isset($args['listing'])) {
        $collectionUser = \Mockery::mock($collectionUser)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type(Projection::class))
            ->andReturn($args['listing'])
            ->getMock();
    }

    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionDriverLicence);
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

    $listing = \Mockery::mock(Listing::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    $testCase->invokeProperty($listing, 'request', $request);

    return $listing;
};

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

test('handleRequest() should return a response 200', function () use ($before) {
    $data = [
        [
            'id'             => 1,
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'birthday'       => '1980-01-01',
            'active'         => true,
            'driver_licence' => [
                'id'        => 1,
                'reference' => 'AAA456789',
            ],
        ],
        [
            'id'             => 2,
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'birthday'       => '1984-01-01',
            'active'         => true,
            'driver_licence' => [
                'id'        => 2,
                'reference' => 'BBB456789',
            ],
        ],
    ];

    $listing = $before($this, ['listing' => $data]);

    expect($listing->handleRequest(['collectionName' => 'User']))
        ->toBeArray()
        ->toEqual(
            [
                'name'    => 'User',
                'content' => [
                    'data'     => [
                        [
                            'type'          => 'User',
                            'id'            => '1',
                            'attributes'    => [
                                'first_name' => 'John',
                                'last_name'  => 'Doe',
                                'birthday'   => '1980-01-01',
                                'active'     => true,
                            ],
                            'relationships' => [
                                'driver_licence' => [
                                    'data' => [
                                        'type' => 'DriverLicence',
                                        'id'   => 1,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type'          => 'User',
                            'id'            => '2',
                            'attributes'    => [
                                'first_name' => 'Jane',
                                'last_name'  => 'Doe',
                                'birthday'   => '1984-01-01',
                                'active'     => true,
                            ],
                            'relationships' => [
                                'driver_licence' => [
                                    'data' => [
                                        'type' => 'DriverLicence',
                                        'id'   => 2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'included' => [
                        [
                            'type'       => 'DriverLicence',
                            'id'         => 1,
                            'attributes' => [
                                'id'        => 1,
                                'reference' => 'AAA456789',
                            ],
                        ],
                        [
                            'type'       => 'DriverLicence',
                            'id'         => 2,
                            'attributes' => [
                                'id'        => 2,
                                'reference' => 'BBB456789',
                            ],
                        ],
                    ],
                ],
            ]
        );
});

test('handleRequestCsv() should return a response 200', function () use ($before) {
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

    $listing = $before($this, ['listing' => $data]);

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

test('checkIp() should check the clientIp in the ip-whitelist-rules', function () {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $data = [
        'data' => [
            'type'       => 'ip-whitelist-rules',
            'id'         => '1',
            'attributes' => [
                'rules'            => [
                    [
                        'type' => 0,
                        'ip'   => '127.0.0.1',
                    ],
                ],
                'use_ip_whitelist' => true,
            ],
        ],
    ];
    $prophet = new Prophet();
    $forestApiRequester = $prophet->prophesize(ForestApiRequester::class);
    $forestApiRequester
        ->get(Argument::type('string'))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode($data, JSON_THROW_ON_ERROR))
        );

    $listing = \Mockery::mock(Listing::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $this->invokeProperty($listing, 'request', Request::createFromGlobals());

    expect($listing->checkIp($forestApiRequester->reveal()))
        ->toBeNull();
});

test('checkIp() throw when the clientIp is not into the ip-whitelist-rules', function () {
    $_SERVER['REMOTE_ADDR'] = '10.10.10.1';

    $data = [
        'data' => [
            'type'       => 'ip-whitelist-rules',
            'id'         => '1',
            'attributes' => [
                'rules'            => [
                    [
                        'type' => 0,
                        'ip'   => '127.0.0.1',
                    ],
                ],
                'use_ip_whitelist' => true,
            ],
        ],
    ];
    $prophet = new Prophet();
    $forestApiRequester = $prophet->prophesize(ForestApiRequester::class);
    $forestApiRequester
        ->get(Argument::type('string'))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode($data, JSON_THROW_ON_ERROR))
        );

    $listing = \Mockery::mock(Listing::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $this->invokeProperty($listing, 'request', Request::createFromGlobals());

    expect(fn () => $listing->checkIp($forestApiRequester->reveal()))
        ->toThrow(HttpException::class, 'IP address rejected (' . $_SERVER['REMOTE_ADDR'] . ')');
});

test('handleRequest() with search should return a response 200 with an attribute meta', function () use ($before) {
    $_GET['search'] = '1980';
    $_GET['searchExtended'] = '0';
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

    $listing = $before($this, ['listing' => $data]);

    expect($listing->handleRequest(['collectionName' => 'User'])['content'])
        ->toHaveKey('meta');
});

test('handleRequest() should return a response 200 with an attribute meta', function () use ($before) {
    $_GET['filters'] = json_encode(['field' => 'id', 'operator' => Operators::SHORTER_THAN, 'value' => 7]);

    $listing = $before($this, []);

    expect(fn () => $listing->handleRequest(['collectionName' => 'User']))
        ->toThrow(ForestException::class, "🌳🌳🌳 The given operator 'Shorter_Than' is not supported by the column: id. The allowed operators are: [Equal]");

});
