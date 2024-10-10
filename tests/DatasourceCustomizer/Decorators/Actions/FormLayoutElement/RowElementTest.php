<?php

namespace Tests\Unit\ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\RowElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

test('throws an exception if fields are empty', function () {
    new RowElement([]);
})->throws(ForestException::class, "Using 'fields' in a 'Row' configuration is mandatory");


test('creates an instance with valid fields', function () {
    $fields = [new DynamicField(type: FieldType::STRING, label: 'field 1'), new DynamicField(type: FieldType::STRING, label: 'field 2')];
    $rowElement = new RowElement($fields);

    expect($rowElement->getFields())->toEqual($fields);
});

test('throws an exception if a field is not an instance of DynamicField', function () {
    new RowElement([new DynamicField(type: FieldType::STRING, label: 'field 1'), 'field 2']);
})->throws(ForestException::class, "A field must be an instance of DynamicField");

test('allows setting fields after instantiation', function () {
    $initialFields = [
        new DynamicField(type: FieldType::STRING, label: 'initial field 1'),
    ];
    $rowElement = new RowElement($initialFields);

    $newFields = [
        new DynamicField(type: FieldType::STRING, label: 'field 1'),
        new DynamicField(type: FieldType::STRING, label: 'field 2'),
    ];
    $rowElement->setFields($newFields);

    expect($rowElement->getFields())->toEqual($newFields);
});
