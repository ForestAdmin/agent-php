<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ArrayType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\TypeGetter;

test('get() when the value is an array of numbers should return the expected type', function () {
    expect(TypeGetter::get([-1, 2, 3]))->toEqual(ArrayType::Number());
});

test('get() when the value is an array of boolean should return the expected type', function () {
    expect(TypeGetter::get([true, false]))->toEqual(ArrayType::Boolean());
});

test('get() when the value is an array of Uuid should return the expected type', function () {
    expect(
        TypeGetter::get(
            [
                '2d162303-78bf-599e-b197-93590ac3d315',
                '2d162303-78bf-599e-b197-93590ac3d315',
            ]
        )
    )->toEqual(ArrayType::Uuid());
});

test('get() when the value is an array of string should return the expected type', function () {
    expect(TypeGetter::get(['str', 'str2', 'str']))->toEqual(ArrayType::String());
});

test('get() when the value is an array of string and the context is an Enum should return the expected type', function () {
    expect(TypeGetter::get(['an enum value'], 'Enum'))->toEqual(ArrayType::Enum());
});

test('get() when the value is an array and a contexType is not a  number,uid,boolean,string or enum  should return null', function () {
    expect(TypeGetter::get([new \DateTime(), new \DateTime()], PrimitiveType::BOOLEAN))->toEqual(ArrayType::Null());
});

test('get() when there is no value should return empty', function () {
    expect(TypeGetter::get([]))->toEqual(ArrayType::Empty());
});

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
    expect(TypeGetter::get('10:30:45'))->toEqual(PrimitiveType::TIMEONLY);
});
