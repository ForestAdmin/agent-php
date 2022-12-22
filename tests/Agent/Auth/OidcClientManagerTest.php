<?php

use ForestAdmin\AgentPHP\Agent\Auth\OAuth2\ForestProvider;
use ForestAdmin\AgentPHP\Agent\Auth\OidcClientManager;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;

use function ForestAdmin\cache;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophet;

function makeAgentForOidc()
{
    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'authSecret'   => AUTH_SECRET,
        'envSecret'    => SECRET,
        'isProduction' => false,
    ];
    new AgentFactory($options, []);
}

/**
 * @return array
 */
function mockedConfig(): array
{
    return [
        'authorization_endpoint'                           => 'https://mock_host/oidc/auth',
        'device_authorization_endpoint'                    => 'https://mock_host/oidc/device/auth',
        'claims_parameter_supported'                       => false,
        'claims_supported'                                 => [
            'sub',
            'email',
            'sid',
            'auth_time',
            'iss',
        ],
        'code_challenge_methods_supported'                 => [
            'S256',
        ],
        'end_session_endpoint'                             => 'https://mock_host/oidc/session/end',
        'grant_types_supported'                            => [
            'authorization_code',
            'urn:ietf:params:oauth:grant-type:device_code',
        ],
        'id_token_signing_alg_values_supported'            => [
            'HS256',
            'RS256',
        ],
        'issuer'                                           => 'https://mock_host',
        'jwks_uri'                                         => 'https://mock_host/oidc/jwks',
        'registration_endpoint'                            => 'https://mock_host/oidc/reg',
        'response_modes_supported'                         => [
            'query',
        ],
        'response_types_supported'                         => [
            'code',
            'none',
        ],
        'scopes_supported'                                 => [
            'openid',
            'email',
            'profile',
        ],
        'subject_types_supported'                          => [
            'public',
        ],
        'token_endpoint_auth_methods_supported'            => [
            'none',
        ],
        'token_endpoint_auth_signing_alg_values_supported' => [],
        'token_endpoint'                                   => 'https://mock_host/oidc/token',
        'request_object_signing_alg_values_supported'      => [
            'HS256',
            'RS256',
        ],
        'request_parameter_supported'                      => false,
        'request_uri_parameter_supported'                  => true,
        'require_request_uri_registration'                 => true,
        'claim_types_supported'                            => [
            'normal',
        ],
    ];
}

function makeForestApiGetAndPost(?string $body = null)
{
    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
        ->get(Argument::type('string'))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode(mockedConfig(), JSON_THROW_ON_ERROR))
        );

    $forestApi
        ->post(Argument::type('string'), Argument::size(0), Argument::size(3), Argument::size(1))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], $body)
        );

    return $forestApi->reveal();
}

test('makeForestProvider() should return a new ForestProvider & the associate cache', function () {
    makeAgentForOidc();

    $forestApi = makeForestApiGetAndPost(json_encode(['client_id' => 1, 'redirect_uris' => ['http://backend.api']], JSON_THROW_ON_ERROR));

    $oidc = new OidcClientManager();
    invokeProperty($oidc, 'forestApi', $forestApi);

    $clientForCallbackUrl = $oidc->makeForestProvider();

    expect($clientForCallbackUrl)
        ->toBeInstanceOf(ForestProvider::class)
        ->and(cache(SECRET . '-client-data'))
        ->toBeArray()
        ->toEqual(['client_id' => 1, 'issuer' => mockedConfig()['issuer'], 'redirect_uri' => 'http://backend.api']);
});

test('makeForestProvider() throw when the API call failed', function () {
    makeAgentForOidc();
    $forestApi = makeForestApiGetAndPost();

    $oidc = new OidcClientManager();
    invokeProperty($oidc, 'forestApi', $forestApi);

    expect(fn () => $oidc->makeForestProvider())->toThrow(ErrorException::class, ErrorMessages::REGISTRATION_FAILED);
});

test('register() should return the body response of the api', function () {
    makeAgentForOidc();

    $data = [
        'token_endpoint_auth_method' => 'none',
        'registration_endpoint'      => mockedConfig()['registration_endpoint'],
        'redirect_uris'              => ['mock_host/callback'],
        'application_type'           => 'web',
    ];

    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
        ->post(Argument::type('string'), Argument::size(0), $data, Argument::size(1))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode(['client_id' => 1], JSON_THROW_ON_ERROR))
        );

    $oidc = new OidcClientManager();
    invokeProperty($oidc, 'forestApi', $forestApi->reveal());

    $register = invokeMethod($oidc, 'register', [&$data]);

    expect($register)
        ->toBeArray()
        ->and($register['client_id'])
        ->toEqual(1);
});

test('retrieve() should return the body response of the api', function () {
    makeAgentForOidc();

    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
        ->get(Argument::type('string'))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode(mockedConfig(), JSON_THROW_ON_ERROR))
        );

    $oidc = new OidcClientManager();
    invokeProperty($oidc, 'forestApi', $forestApi->reveal());

    $retrieve = invokeMethod($oidc, 'retrieve');

    expect($retrieve)
        ->toBeArray()
        ->toEqual(mockedConfig());
});

test('retrieve() throw when the call api failed', function () {
    makeAgentForOidc();

    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
        ->get(Argument::type('string'))
        ->shouldBeCalled()
        ->willThrow(new \RuntimeException());

    $oidc = new OidcClientManager();
    invokeProperty($oidc, 'forestApi', $forestApi->reveal());

    expect(fn () => invokeMethod($oidc, 'retrieve'))
        ->toThrow(ErrorException::class, ErrorMessages::OIDC_CONFIGURATION_RETRIEVAL_FAILED);
});
