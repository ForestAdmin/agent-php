<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\TypeGetter;

test('get() when the value is a number should return the expected type', function () {
    expect(TypeGetter::get(1526, 'Point'))->toEqual(PrimitiveType::NUMBER);
});

test('get() when there are 2 values and the given context is a Point should return the expected type', function () {
    expect(TypeGetter::get('2,3', 'Point'))->toEqual(PrimitiveType::POINT);
});

test('get() when the value is a DateTime should return the expected type', function () {
    expect(TypeGetter::get(new \DateTime()))->toEqual(PrimitiveType::DATE);
});

test('get() when the value is a string and the typeContext is a number should return the expected type number', function () {
    expect(TypeGetter::get('1', PrimitiveType::NUMBER))->toEqual(PrimitiveType::NUMBER);
});

test('get() when the value is a json should return the expected type json', function () {
    expect(TypeGetter::get(json_encode(['key' => 'value'])))->toEqual(PrimitiveType::JSON);
});

test('get() when the value is a time should return the expected type PrimitiveType::TIMEONLY', function () {
    expect(TypeGetter::get('10:30:45', 'Date'))->toEqual(PrimitiveType::TIMEONLY);
});
