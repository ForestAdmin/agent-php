<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\RowElement;

test('can set and get fields using magic methods', function () {
    $fields = ['field1', 'field2'];
    $rowElement = new RowElement($fields);

    expect($rowElement->fields)->toBe($fields);

    $newFields = ['field3', 'field4'];
    $rowElement->fields = $newFields;

    expect($rowElement->fields)->toBe($newFields);
});

test('can check if fields are set using __isset', function () {
    $fields = ['field1', 'field2'];
    $rowElement = new RowElement($fields);

    expect(isset($rowElement->fields))->toBeTrue()
        ->and(isset($rowElement->nonExistentField))->toBeFalse();
});

test('can set and get arbitrary properties using magic methods', function () {
    $rowElement = new RowElement([]);

    $rowElement->customProperty = 'value';
    expect($rowElement->customProperty)->toBe('value')
        ->and(isset($rowElement->customProperty))->toBeTrue();

});
