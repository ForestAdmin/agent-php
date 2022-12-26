<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use Illuminate\Support\Str;

test('header() should work', function () {
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $request = Request::createFromGlobals();

    expect($request->header('content-type'))->toEqual('application/json');
});

test('bearerToken() should work', function () {
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $request = Request::createFromGlobals();

    expect($request->bearerToken())->toEqual(Str::replace('Bearer ', '', BEARER));
});

test('all() should return array with the contents of POST and GET', function () {
    $_GET['key1'] = 'foo1';
    $_POST['key2'] = 'foo2';
    $request = Request::createFromGlobals();

    expect($request->all())->toEqual(['key1' => 'foo1', 'key2' => 'foo2']);
});

test('input() should return array with the contents of POST and GET with no args', function () {
    $_GET['key1'] = 'foo1';
    $_POST['key2'] = 'foo2';
    $request = Request::createFromGlobals();

    expect($request->input())->toEqual(['key1' => 'foo1', 'key2' => 'foo2']);
});

test('input() should return the value of the key', function () {
    $_GET['key1'] = 'foo1';
    $_POST['key2'] = 'foo2';
    $request = Request::createFromGlobals();

    expect($request->input('key1'))->toEqual('foo1');
});

test('input() should return the default value when the key does not exist', function () {
    $_GET['key1'] = 'foo1';
    $_POST['key2'] = 'foo2';
    $request = Request::createFromGlobals();

    expect($request->input('key11', 'defaultValue'))->toEqual('defaultValue');
});
