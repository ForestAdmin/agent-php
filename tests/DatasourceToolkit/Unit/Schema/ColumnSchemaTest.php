<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

dataset('columnSchema', function () {
    yield new ColumnSchema(
        PrimitiveType::STRING,
        [],
        false,
        false,
        true,
        'Column',
        'foo',
        [],
        [],

    );
});

test('getColumnType() should return columnType value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->getColumnType())->toEqual(PrimitiveType::STRING);
})->with('columnSchema');

test('getFilterOperators() should return filterOperators value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->getFilterOperators())->toEqual([]);
})->with('columnSchema');

test('isPrimaryKey() should return primaryKey value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->isPrimaryKey())->toEqual(false);
})->with('columnSchema');

test('isReadOnly() should return readOnly value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->isReadOnly())->toEqual(false);
})->with('columnSchema');

test('isSortable() should return sortable value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->isSortable())->toEqual(true);
})->with('columnSchema');

test('setSortable() should return sortable value', function (ColumnSchema $columnSchema) {
    $columnSchema->setSortable(false);
    expect($columnSchema->isSortable())->toEqual(false);
})->with('columnSchema');

test('getType() should return type value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->getType())
        ->toEqual('Column');
})->with('columnSchema');

test('getDefaultValueType() should return defaultValue value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->getDefaultValue())
        ->toEqual('foo');
})->with('columnSchema');

test('getEnumValues() should return enumValues value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->getEnumValues())
        ->toEqual([]);
})->with('columnSchema');

test('getValidation() should return validation value', function (ColumnSchema $columnSchema) {
    expect($columnSchema->getValidation())
        ->toEqual([]);
})->with('columnSchema');
