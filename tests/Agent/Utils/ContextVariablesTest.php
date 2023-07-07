<?php


use ForestAdmin\AgentPHP\Agent\Utils\ContextVariables;

function contextVariableFactory()
{
    $user = [
        'id'              => 1,
        'firstName'       => 'John',
        'lastName'        => 'Doe',
        'fullName'        => 'John Doe',
        'email'           => 'john.doe@domain.com',
        'tags'            => ['foo' => 'bar'],
        'roleId'          => 1,
        'permissionLevel' => 'admin',
    ];

    $team = [
        'id'   => 1,
        'name' => 'Operations',
    ];

    $requestContextVariables = ['foo.id' => 100];

    return compact('user', 'team', 'requestContextVariables');
}

test('getValue() should return the request context variable key when the key is not present into the user data', function () {
    $contextVariables = new ContextVariables(...contextVariableFactory());

    expect($contextVariables->getValue('foo.id'))->toEqual(100);
});

test('getValue() should return the corresponding value from the key provided of the user data', function () {
    $contextVariables = new ContextVariables(...contextVariableFactory());

    expect($contextVariables->getValue('currentUser.firstName'))->toEqual('John');
    expect($contextVariables->getValue('currentUser.tags.foo'))->toEqual('bar');
    expect($contextVariables->getValue('currentUser.team.id'))->toEqual(1);
});
