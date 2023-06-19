<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ChartValidator;

test('validate() should throw', function () {
    expect(static fn () => ChartValidator::validate(true, ['label' => 'foo', 'value' => '10'], 'key, value'))
        ->toThrow(ForestException::class, "🌳🌳🌳 The result columns must be named 'key, value' instead of 'label,value'");
});

test('validate() should return true', function () {
    expect(ChartValidator::validate(false, ['label' => 'foo', 'value' => '10'], 'label, value'))->toBeTrue();
});
