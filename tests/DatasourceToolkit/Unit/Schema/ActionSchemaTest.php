<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\ActionScope;

dataset('actionSchema', function () {
    yield new ActionSchema(
        ActionScope::single(),
        false,
        false,
    );
});

test('getScope() should return scope value', function (ActionSchema $actionSchema) {
    expect($actionSchema->getScope())
        ->toBeInstanceOf(ActionScope::class)
        ->toEqual(ActionScope::single());
})->with('actionSchema');

test('isGenerateFile() should return generateFile value', function (ActionSchema $actionSchema) {
    expect($actionSchema->isGenerateFile())
        ->toEqual(false);
})->with('actionSchema');

test('isStaticForm() should return staticForm value', function (ActionSchema $actionSchema) {
    expect($actionSchema->isStaticForm())
        ->toEqual(false);
})->with('actionSchema');
