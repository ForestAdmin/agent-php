<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ForestAdmin\AgentPHP\Agent\Auth\AuthManager;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Prophecy\Argument;
use Prophecy\Prophet;

function user()
{
    return [
        'id'               => 1,
        'email'            => 'john.doe@example.com',
        'first_name'       => 'John',
        'last_name'        => 'Doe',
        'team'             => 'Operations',
        'tags'             => [
            0 => [
                'key'   => 'demo',
                'value' => '1234',
            ],
        ],
        'rendering_id'     => 1,
        'exp'              => (new \DateTime())->modify('+ 1 hour')->format('U'),
        'permission_level' => 'admin',
    ];
}

function factoryAuthentication(): Authentication
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'Europe/Paris';

//    $collectionUser = new Collection($datasource, 'User');
//    $collectionUser->addFields(
//        [
//            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
//            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
//            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
//        ]
//    );
//
//    if (isset($args['count'])) {
//        $collectionUser = mock($collectionUser)
//            ->shouldReceive('aggregate')
//            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class))
//            ->andReturn(count($args['count']))
//            ->getMock();
//    }
//
//    $datasource->addCollection($collectionUser);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasources([$datasource]);

    $request = Request::createFromGlobals();

    $prophet = new Prophet();
    $auth = $prophet->prophesize(AuthManager::class);
    $auth
        ->start(1)
        ->shouldBeCalled()
        ->willReturn('http://localhost/oidc/auth?state=%7B%22renderingId%22%3A' . $_GET['renderingId'] . '%7D&scope=openid%20profile%20email&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%3A3000%2Fforest%2Fauthentication%2Fcallback&client_id=TEST');

    $auth->verifyCodeAndGenerateToken(Argument::any())
        ->shouldBeCalled()
        ->willReturn(JWT::encode(user(), SECRET, 'HS256'));

    $authentication = mock(Authentication::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('auth')
        ->andReturn($auth->reveal())
        ->getMock();

    invokeProperty($authentication, 'request', $request);

    return $authentication;
}

test('make() should return a new instance of Authentication with routes', function () {
    $authentication = Authentication::make();

    expect($authentication)->toBeInstanceOf(Authentication::class)
        ->and($authentication->getRoutes())
        ->toHaveKeys(['forest.authentication', 'forest.authentication-callback', 'forest.logout']);
});

test('handleAuthentication() should return a response 200', function () {
    $_GET['renderingId'] = 1;

    $authentication = factoryAuthentication();

    expect($authentication->handleAuthentication())
        ->toBeArray()
        ->toEqual(
            [
                'content' => [
                    'authorizationUrl' => 'http://localhost/oidc/auth?state=%7B%22renderingId%22%3A' . $_GET['renderingId'] . '%7D&scope=openid%20profile%20email&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%3A3000%2Fforest%2Fauthentication%2Fcallback&client_id=TEST',
                ],
            ]
        );
});

test('handleAuthenticationCallback() should return a response 200', function () {
    $_GET['renderingId'] = 1;
    $authentication = factoryAuthentication();
    $token = JWT::encode(user(), SECRET, 'HS256');

    expect($authentication->handleAuthenticationCallback())
        ->toBeArray()
        ->toEqual(
            [
                'content' => [
                    'token'     => $token,
                    'tokenData' => JWT::decode($token, new Key(SECRET, 'HS256')),
                ],
            ]
        );
});

test('handleAuthenticationLogout() should return a 204 response', function () {
    $_GET['renderingId'] = 1;
    $authentication = factoryAuthentication();

    expect($authentication->handleAuthenticationLogout())
        ->toBeArray()
        ->toEqual(
            [
                'content' => null,
                'status'  => 204,
            ]
        );
});

test('auth() should return an AuthManager instance', function () {
    $_GET['renderingId'] = 1;
    $authentication = factoryAuthentication();

    expect($authentication->auth())->toBeInstanceOf(AuthManager::class);
});

test('handleRequest() throw when renderingId is missing', function () {
    $_GET['renderingId'] = null;

    $authentication = factoryAuthentication();

    expect(fn () => $authentication->handleAuthentication())
        ->toThrow(ErrorException::class, ErrorMessages::MISSING_RENDERING_ID);
});

test('handleRequest() throw when renderingId is not a numeric value', function () {
    $_GET['renderingId'] = 'foo';

    $authentication = factoryAuthentication();

    expect(fn () => $authentication->handleAuthentication())
        ->toThrow(ErrorException::class, ErrorMessages::INVALID_RENDERING_ID);
});
