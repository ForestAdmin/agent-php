<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ForestAdmin\AgentPHP\Agent\Auth\OAuth2\ForestResourceOwner;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;

use function ForestAdmin\config;

dataset('forestResourceOwner', function () {
    yield new ForestResourceOwner(
        [
            'type'                              => 'users',
            'id'                                => '1',
            'first_name'                        => 'John',
            'last_name'                         => 'Doe',
            'email'                             => 'jdoe@forestadmin.com',
            'teams'                             => [
                0 => 'Operations',
            ],
            'tags'                              => [
                0 => [
                    'key'   => 'demo',
                    'value' => '1234',
                ],
            ],
            'two_factor_authentication_enabled' => false,
            'two_factor_authentication_active'  => false,
            'permission_level'                  => 'admin',
        ],
        1234,
    );
});

test('getId() should return user id', function (ForestResourceOwner $forestResourceOwner) {
    expect($forestResourceOwner->getId())->toEqual(1);
})->with('forestResourceOwner');

test('toArray() should return all user data', function (ForestResourceOwner $forestResourceOwner) {
    expect($forestResourceOwner->toArray())
        ->toBeArray()
        ->toHaveKeys(['id', 'first_name', 'last_name', 'email', 'teams', 'tags', 'two_factor_authentication_enabled', 'two_factor_authentication_active', 'permission_level'])
        ->toEqual([
            'type'                              => 'users',
            'id'                                => '1',
            'first_name'                        => 'John',
            'last_name'                         => 'Doe',
            'email'                             => 'jdoe@forestadmin.com',
            'teams'                             => [
                0 => 'Operations',
            ],
            'tags'                              => [
                0 => [
                    'key'   => 'demo',
                    'value' => '1234',
                ],
            ],
            'two_factor_authentication_enabled' => false,
            'two_factor_authentication_active'  => false,
            'permission_level'                  => 'admin',
        ]);
})->with('forestResourceOwner');

test('expirationInSeconds() should return a timestamp', function (ForestResourceOwner $forestResourceOwner) {
    expect($forestResourceOwner->expirationInSeconds())->toBeInt();
})->with('forestResourceOwner');

test('makeJwt() should return a JWT token', function (ForestResourceOwner $forestResourceOwner) {
    $options = [
        'projectDir' => sys_get_temp_dir(),
        'authSecret' => AUTH_SECRET,
    ];
    (new AgentFactory($options, []));

    $result = JWT::decode($forestResourceOwner->makeJwt(), new Key(AUTH_SECRET, 'HS256'));

    expect($result->id)->toEqual(1);
})->with('forestResourceOwner');
