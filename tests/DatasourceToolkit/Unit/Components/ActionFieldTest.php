<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\ActionField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Concerns\ActionFieldType;

dataset('actionField', function () {
    yield new ActionField(
        ActionFieldType::EnumList(),
        'foo',
        false,
        'demo-action-field',
        false,
        false,
        'bar',
        [1, 2],
        '__collection__'
    );
});

test('getId() should return id value', function (ActionField $actionField) {
    expect($actionField->getType())
        ->toBeInstanceOf(ActionFieldType::class)
        ->toEqual(ActionFieldType::EnumList());
})->with('actionField');

test('label() should return user label value', function (ActionField $actionField) {
    expect($actionField->getLabel())
        ->toEqual('foo');
})->with('actionField');

test('isWatchChanges() should return user watchChanges value', function (ActionField $actionField) {
    expect($actionField->isWatchChanges())
        ->toEqual(false);
})->with('actionField');

test('getDescription() should return user description value', function (ActionField $actionField) {
    expect($actionField->getDescription())
        ->toEqual('demo-action-field');
})->with('actionField');

test('isRequired() should return user required value', function (ActionField $actionField) {
    expect($actionField->isRequired())
        ->toEqual(false);
})->with('actionField');

test('isReadOnly() should return user readOnly value', function (ActionField $actionField) {
    expect($actionField->isReadOnly())
        ->toEqual(false);
})->with('actionField');

test('getValue() should return user value content', function (ActionField $actionField) {
    expect($actionField->getValue())
        ->toEqual('bar');
})->with('actionField');

test('getEnumValues() should return user enumValues value', function (ActionField $actionField) {
    expect($actionField->getEnumValues())
        ->toEqual([1, 2]);
})->with('actionField');

test('getCollectionName() should return user collectionName value', function (ActionField $actionField) {
    expect($actionField->getCollectionName())
        ->toEqual('__collection__');
})->with('actionField');
