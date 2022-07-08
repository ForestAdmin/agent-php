<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use JsonException;
use function PHPUnit\Framework\isEmpty;

class TypeGetter
{
    public static function get($value, PrimitiveType $typeContext): PrimitiveType | ValidationType
    {
        if (is_array($value)) {
            return self::getArrayType($value, $typeContext);
        }

        if (is_string($value)) {
            return self::getTypeFromString($value, $typeContext);
        }

        if (is_numeric($value) && ! is_nan((float) $value)) {
            return PrimitiveType::Number();
        }
//        if (value instanceof Date && DateTime.fromJSDate(value).isValid) return 'Date'; //TODO

        if (is_bool($value)) {
            return PrimitiveType::Boolean();
        }

//        if (typeof value === 'object' && typeContext === 'Json') return 'Json'; //TODO

        return ValidationType::Null();
    }

    private static function getTypeFromString($value, PrimitiveType $typeContext): PrimitiveType
    {
        if (in_array($typeContext, [PrimitiveType::Enum(), PrimitiveType::String()], true)) {
            return $typeContext;
        }

//        if (uuidValidate(value)) return 'Uuid'; TODO

        if (self::isValidDate($value)) {
            return self::getDateType($value);
        }

        if (self::isJson($value)) {
            return PrimitiveType::Json();
        }

        if (self::isPoint($value, $typeContext)) {
            return PrimitiveType::Point();
        }

        return PrimitiveType::String();
    }

    private static function isPoint(string $value, PrimitiveType $typeContext): bool
    {
        $potentialPoint = explode(',', $value);

        return count($potentialPoint) === 2 &&
            $typeContext === PrimitiveType::Point() &&
            self::get($potentialPoint, PrimitiveType::Number()) === ValidationType::Number();
    }

    private static function isJson(string $value): bool
    {
        try {
            return (bool) json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return false;
        }
    }

    private static function getArrayType(array $value, PrimitiveType $typeContext): ValidationType
    {
        if (isEmpty($value)) {
            return ValidationType::Empty();
        }

        if (self::isArrayOf(PrimitiveType::Number(), $value, $typeContext)) {
            return ValidationType::Number();
        }

        if (self::isArrayOf(PrimitiveType::Uuid(), $value, $typeContext)) {
            return ValidationType::Uuid();
        }

        if (self::isArrayOf(PrimitiveType::Boolean(), $value, $typeContext)) {
            return ValidationType::Boolean();
        }

        if (self::isArrayOf(PrimitiveType::String(), $value, $typeContext)) {
            return ValidationType::String();
        }

        if (self::isArrayOf(PrimitiveType::Enum(), $value, $typeContext)) {
            return ValidationType::Enum();
        }

        return ValidationType::Null();
    }

    private static function isArrayOf(PrimitiveType $primitiveType, array $values, PrimitiveType $typeContext): bool
    {
        foreach ($values as $value) {
            if (self::get($value, $typeContext) !== $primitiveType) {
                return false;
            }
        }

        return true;
    }

    private static function isValidDate(string $value): bool
    {
        return (bool) strtotime($value);
    }

    private static function getDateType(string $value): PrimitiveType
    {
        $time = strtotime($value);

        if (date('Y-m-d', $time) === $value) {
            return PrimitiveType::Dateonly();
        }

        if (date('H:i:s', $time) === $value) {
            return PrimitiveType::Timeonly();
        }

        return PrimitiveType::Date();
    }
}
