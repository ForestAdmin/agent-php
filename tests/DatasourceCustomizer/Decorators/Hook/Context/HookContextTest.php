<?php

use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ForbiddenError;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\UnprocessableError;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ValidationError;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\HookContext;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

beforeEach(closure: function () {
    global $collection;

    $collection = new Collection(new Datasource(), 'Book');
});

test('throwValidationError() should throw ValidationError', function ($caller) {
    global $collection;
    $context = new HookContext($collection, $caller);
    $errorMessage = 'validation error';

    expect(fn () => $context->throwValidationError($errorMessage))->toThrow(ValidationError::class, $errorMessage);
})->with('caller');

test('throwForbiddenError() should throw ForbidenError', function ($caller) {
    global $collection;
    $context = new HookContext($collection, $caller);
    $errorMessage = 'forbidden error';

    expect(fn () => $context->throwForbiddenError($errorMessage))->toThrow(ForbiddenError::class, $errorMessage);
})->with('caller');

test('throwError() should throw UnprocessableError', function ($caller) {
    global $collection;
    $context = new HookContext($collection, $caller);
    $errorMessage = 'unprocessable error';

    expect(fn () => $context->throwError($errorMessage))->toThrow(UnprocessableError::class, $errorMessage);
})->with('caller');
