<?php

use ForestAdmin\AgentPHP\Agent\Auth\AuthManager;
use ForestAdmin\AgentPHP\Agent\Auth\OAuth2\ForestProvider;
use ForestAdmin\AgentPHP\Agent\Auth\OAuth2\ForestResourceOwner;
use ForestAdmin\AgentPHP\Agent\Auth\OidcClientManager;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;
use League\OAuth2\Client\Token\AccessToken;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

function factoryOidc(string $return, bool $withAuthorization = false): ObjectProphecy
{
    $prophet = new Prophet();
    $resourceOwner = $prophet->prophesize(ForestResourceOwner::class);
    $resourceOwner
        ->makeJwt()
        ->willReturn($return);

    $provider = $prophet->prophesize(ForestProvider::class);
    $provider
        ->getAccessToken(Argument::type('string'), Argument::size(2))
        ->willReturn(
            new AccessToken(['access_token' => 'token'])
        );
    $provider
        ->getResourceOwner(Argument::any())
        ->willReturn(
            $resourceOwner->reveal()
        );
    $provider
        ->setRenderingId(Argument::type('integer'))
        ->willReturn($provider);

    if ($withAuthorization) {
        $provider->getAuthorizationUrl(Argument::any())
            ->shouldBeCalled()
            ->willReturn(
                'http://localhost/oidc/auth?state=%7B%22renderingId%22%3A1%7D&scope=openid%20profile%20email&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Ffoo%2Fauthentication%2Fcallback&client_id=TEST'
            );
    }

    $oidc = $prophet->prophesize(OidcClientManager::class);
    $oidc
        ->makeForestProvider()
        ->shouldBeCalled()
        ->willReturn(
            $provider->reveal()
        );

    return $oidc;
}

test('start() should return the renderingId', function () {
    $oidc = factoryOidc('123ABC', true);
    $auth = new AuthManager($oidc->reveal());

    $start = $auth->start(1);
    parse_str(parse_url($start, PHP_URL_QUERY), $output);

    expect($output['state'])->toEqual('{"renderingId":1}');
});

test('verifyCodeAndGenerateToken() should return the token', function () {
    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'authSecret'    => AUTH_SECRET,
        'isProduction' => false,
        'debug'        => false,
    ];
    new AgentFactory($options, []);

    $return = '123ABC';
    $oidc = factoryOidc($return);
    $auth = new AuthManager($oidc->reveal());

    $data = ['code' => 'test', 'state' => '{"renderingId":1}'];
    $token = $auth->verifyCodeAndGenerateToken($data);

    expect($return)->toEqual($token);
});

test('getRenderingIdFromState() should return the renderingId', function () {
    $return = '123ABC';
    $oidc = factoryOidc($return);
    $auth = new AuthManager($oidc->reveal());

    $data = ['state' => '{"renderingId":1}'];
    $renderingId = invokeMethod($auth, 'getRenderingIdFromState', $data);

    expect($renderingId)->toEqual(1);
});

test('getRenderingIdFromState() throw when the renderingId is not valid', function () {
    $return = '123ABC';
    $oidc = factoryOidc($return);
    $auth = new AuthManager($oidc->reveal());
    $data = ['state' => '{"renderingId":"AA"}'];

    expect(fn () => invokeMethod($auth, 'getRenderingIdFromState', $data))
        ->toThrow(ErrorException::class, ErrorMessages::INVALID_STATE_FORMAT);
});

test('stateIsValid() should return true when the state is valid', function () {
    $return = '123ABC';
    $oidc = factoryOidc($return);
    $auth = new AuthManager($oidc->reveal());

    $data = ['code' => 'test', 'state' => '{"renderingId":1}'];
    $state = invokeMethod($auth, 'stateIsValid', [&$data]);

    expect($state)->toBeTrue();
});

test('stateIsValid() throw when the state parameter is missing', function () {
    $return = '123ABC';
    $oidc = factoryOidc($return);
    $auth = new AuthManager($oidc->reveal());

    $data = ['code' => 'test'];

    expect(fn () => invokeMethod($auth, 'stateIsValid', [&$data]))
        ->toThrow(ErrorException::class, ErrorMessages::INVALID_STATE_MISSING);
});

test('stateIsValid() throw when the state is not valid', function () {
    $return = '123ABC';
    $oidc = factoryOidc($return);
    $auth = new AuthManager($oidc->reveal());

    $data = ['code' => 'test', 'state' => '{}'];

    expect(fn () => invokeMethod($auth, 'stateIsValid', [&$data]))
        ->toThrow(ErrorException::class, ErrorMessages::INVALID_STATE_RENDERING_ID);
});

