<?php

use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ForbiddenError;
use ForestAdmin\AgentPHP\Agent\Http\Traits\ErrorHandling;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

test('getErrorStatus() should return 500 by default', function () {
    $trait = $this->getObjectForTrait(ErrorHandling::class);

    expect($trait->getErrorStatus(new ForestException('test')))->toEqual(500);
});

test('getErrorStatus() should return the status code corresponding to the error', function () {
    $trait = $this->getObjectForTrait(ErrorHandling::class);

    expect($trait->getErrorStatus(new ForbiddenError('test')))->toEqual(403);
});

test('getErrorName() should return the short classname by default', function () {
    $trait = $this->getObjectForTrait(ErrorHandling::class);

    expect($trait->getErrorName(new ForestException('test')))->toEqual('ForestException');
});

test('getErrorName() should return the name corresponding to the error', function () {
    $trait = $this->getObjectForTrait(ErrorHandling::class);
    $error = new ForbiddenError('test');

    expect($trait->getErrorName($error))->toEqual($error->getName());
});

test('getErrorHeaders() should return an empty array by default', function () {
    $trait = $this->getObjectForTrait(ErrorHandling::class);

    expect($trait->getErrorHeaders(new ForestException('test')))->toEqual([]);
});

test('getErrorHeaders() should return the headers config if defined', function () {
    $trait = $this->getObjectForTrait(ErrorHandling::class);
    $error = new ForbiddenError('test', ['Content-Type' => 'text/plain']);

    expect($trait->getErrorHeaders($error))->toEqual($error->getHeaders());
});

test('getErrorMessage() should return the original message when error is subclass of HttpException', function () {
    $this->buildAgent(new Datasource());
    $trait = $this->getObjectForTrait(ErrorHandling::class);

    expect($trait->getErrorMessage(new ForbiddenError('test')))->toEqual('test');
});

test('getErrorMessage() should call the closure if defined', function () {
    $this->buildAgent(new Datasource());
    $this->agent->createAgent([
        'customizeErrorMessage' => fn ($error) => 'my custom message error',
    ]);
    $trait = $this->getObjectForTrait(ErrorHandling::class);

    expect($trait->getErrorMessage(new ForestException('test')))->toEqual('my custom message error');
});

test('getErrorMessage() should return "Unexpected error" by default', function () {
    $this->buildAgent(new Datasource());
    $trait = $this->getObjectForTrait(ErrorHandling::class);

    expect($trait->getErrorMessage(new \Exception('test')))->toEqual('Unexpected error');
});
