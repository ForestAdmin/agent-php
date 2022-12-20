<?php

use ForestAdmin\AgentPHP\DatasourceDoctrine\Utils\DataTypes;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

test('getType() should return the correct PrimitiveType value', function () {
    expect(DataTypes::getType('integer'))
        ->toEqual(PrimitiveType::NUMBER)
        ->and(DataTypes::getType('float'))
        ->toEqual(PrimitiveType::NUMBER)
        ->and(DataTypes::getType('date'))
        ->toEqual(PrimitiveType::DATEONLY)
        ->and(DataTypes::getType('datetime_immutable'))
        ->toEqual(PrimitiveType::DATE)
        ->and(DataTypes::getType('datetime'))
        ->toEqual(PrimitiveType::DATE)
        ->and(DataTypes::getType('boolean'))
        ->toEqual(PrimitiveType::BOOLEAN)
        ->and(DataTypes::getType('time'))
        ->toEqual(PrimitiveType::TIMEONLY)
        ->and(DataTypes::getType('json'))
        ->toEqual(PrimitiveType::JSON)
        ->and(DataTypes::getType('any-other-values'))
        ->toEqual(PrimitiveType::STRING);
});

test('renderValue() should return the correct format value', function () {
    expect(DataTypes::renderValue('integer', 5))
        ->toEqual(5)
        ->and(DataTypes::renderValue('float', 5.5))
        ->toEqual(5.5)
        ->and(DataTypes::renderValue('date', new \DateTime('2022-01-01')))
        ->toEqual('2022-01-01')
        ->and(DataTypes::renderValue('datetime_immutable', new \DateTime('2022-01-01 13:30:45')))
        ->toEqual('2022-01-01 13:30:45')
        ->and(DataTypes::renderValue('datetime', new \DateTime('2022-01-01 13:30:45')))
        ->toEqual('2022-01-01 13:30:45')
        ->and(DataTypes::renderValue('boolean', true))
        ->toEqual(true)
        ->and(DataTypes::renderValue('time', new \DateTime('2022-01-01 13:30:45')))
        ->toEqual('13:30:45')
        ->and(DataTypes::renderValue('json', ['foo' => 'bar']))
        ->toEqual(['foo' => 'bar'])
        ->and(DataTypes::renderValue('any-other-values', 'hello there'))
        ->toEqual('hello there');
});
