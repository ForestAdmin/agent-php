<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ForestAdmin\AgentPHP\Agent\Auth\AuthManager;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Prophecy\Argument;
use Prophecy\Prophet;

beforeEach(function () {
    $this->buildAgent(new Datasource());
    $_GET['renderingId'] = 1;
    $user = [
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
    $request = Request::createFromGlobals();

    $prophet = new Prophet();
    $auth = $prophet->prophesize(AuthManager::class);
    $auth
        ->start(1)
        ->shouldBeCalled()
        ->willReturn('http://localhost/oidc/auth?state=%7B%22renderingId%22%3A' . $_GET['renderingId'] . '%7D&scope=openid%20profile%20email&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%3A3000%2Fforest%2Fauthentication%2Fcallback&client_id=TEST');

    $auth->verifyCodeAndGenerateToken(Argument::any())
        ->shouldBeCalled()
        ->willReturn(JWT::encode($user, AUTH_SECRET, 'HS256'));

    $authentication = mock(Authentication::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('auth')
        ->andReturn($auth->reveal())
        ->shouldReceive('checkIp')
        ->getMock();

    $this->invokeProperty($authentication, 'request', $request);

    $this->bucket['authentication'] = $authentication;
    $this->bucket['user'] = $user;
});

test('make() should return a new instance of Authentication with routes', function () {
    $authentication = Authentication::make();

    expect($authentication)->toBeInstanceOf(Authentication::class)
        ->and($authentication->getRoutes())
        ->toHaveKeys(['forest.authentication', 'forest.authentication-callback', 'forest.logout']);
});

test('handleAuthentication() should return a response 200', function () {
    $authentication = $this->bucket['authentication'];

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
    $authentication = $this->bucket['authentication'];
    $token = JWT::encode($this->bucket['user'], AUTH_SECRET, 'HS256');

    expect($authentication->handleAuthenticationCallback())
        ->toBeArray()
        ->toEqual(
            [
                'content' => [
                    'token'     => $token,
                    'tokenData' => JWT::decode($token, new Key(AUTH_SECRET, 'HS256')),
                ],
            ]
        );
});

test('handleAuthenticationLogout() should return a 204 response', function () {
    $authentication = $this->bucket['authentication'];

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
    $authentication = $this->bucket['authentication'];

    expect($authentication->auth())->toBeInstanceOf(AuthManager::class);
});

test('handleRequest() throw when renderingId is missing', function () {
    $_GET['renderingId'] = null;
    $authentication = $this->bucket['authentication'];
    $this->invokeProperty($authentication, 'request', Request::createFromGlobals());


    expect(fn () => $authentication->handleAuthentication())
        ->toThrow(ErrorException::class, ErrorMessages::MISSING_RENDERING_ID);
});

test('handleRequest() throw when renderingId is not a numeric value', function () {
    $_GET['renderingId'] = 'foo';
    $authentication = $this->bucket['authentication'];
    $this->invokeProperty($authentication, 'request', Request::createFromGlobals());

    expect(fn () => $authentication->handleAuthentication())
        ->toThrow(ErrorException::class, ErrorMessages::INVALID_RENDERING_ID);
});
