<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

beforeEach(function () {
    $this->bucket['columnSchema'] = new ColumnSchema(PrimitiveType::STRING, [], false, false, true, 'Column', 'foo', [], []);
});

test('getColumnType() should return columnType value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->getColumnType())->toEqual(PrimitiveType::STRING);
});

test('getFilterOperators() should return filterOperators value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->getFilterOperators())->toEqual([]);
});

test('isPrimaryKey() should return primaryKey value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->isPrimaryKey())->toEqual(false);
});

test('isReadOnly() should return readOnly value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->isReadOnly())->toEqual(false);
});

test('isSortable() should return sortable value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->isSortable())->toEqual(true);
});

test('setSortable() should return sortable value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    $columnSchema->setSortable(false);
    expect($columnSchema->isSortable())->toEqual(false);
});

test('getType() should return type value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->getType())
        ->toEqual('Column');
});

test('getDefaultValueType() should return defaultValue value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->getDefaultValue())
        ->toEqual('foo');
});

test('getEnumValues() should return enumValues value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->getEnumValues())
        ->toEqual([]);
});

test('getValidation() should return validation value', function () {
    $columnSchema = $this->bucket['columnSchema'];
    expect($columnSchema->getValidation())
        ->toEqual([]);
});
