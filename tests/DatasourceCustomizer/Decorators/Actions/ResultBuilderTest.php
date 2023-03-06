<?php


use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ResultBuilder;

test('success() should work without arguments', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->success())
        ->toEqual(
            [
                'is_action' => true,
                'type'      => 'Success',
                'success'   => 'Success',
                'refresh'   => ['relationships' => []],
                'html'      => null,
            ]
        );
});

test('success() should work with specific message and options', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->success('It works !', ['html' => '<div>html content</div>']))
        ->toEqual(
            [
                'is_action' => true,
                'type'      => 'Success',
                'success'   => 'It works !',
                'refresh'   => ['relationships' => []],
                'html'      => '<div>html content</div>',
            ]
        );
});

test('error() should work without arguments', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->error())
        ->toEqual(
            [
                'status'    => 400,
                'is_action' => true,
                'type'      => 'Error',
                'error'     => 'Error',
                'html'      => null,
            ]
        );
});

test('error() should work with specific message and options', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->error('It not works !', ['html' => '<div>html content</div>']))
        ->toEqual(
            [
                'status'    => 400,
                'is_action' => true,
                'type'      => 'Error',
                'error'     => 'It not works !',
                'html'      => '<div>html content</div>',
            ]
        );
});

test('webhook() should work without arguments', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->webhook('test.com'))
        ->toEqual(
            [
                'is_action' => true,
                'type'      => 'Webhook',
                'webhook'   => [
                    'url'     => 'test.com',
                    'method'  => 'POST',
                    'headers' => [],
                    'body'    => [],
                ],
            ]
        );
});

test('webhook() should work with specific options', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->webhook('test.com', 'PATCH', ['content-type' => 'application/json'], ['key' => 'value']))
        ->toEqual(
            [
                'is_action' => true,
                'type'      => 'Webhook',
                'webhook'   => [
                    'url'     => 'test.com',
                    'method'  => 'PATCH',
                    'headers' => ['content-type' => 'application/json'],
                    'body'    => ['key' => 'value'],
                ],
            ]
        );
});

test('file() should work', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->file('id,name,email', 'test.csv', 'text/csv'))
        ->toEqual(
            [
                'is_action' => true,
                'type'      => 'File',
                'name'      => 'test.csv',
                'mimeType'  => 'text/csv',
                'stream'    => 'id,name,email',
            ]
        );
});

test('redirectTo() should work', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->redirectTo('test.com'))
        ->toEqual(
            [
                'is_action'  => true,
                'type'       => 'Redirect',
                'redirectTo' => 'test.com',
            ]
        );
});
