<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use JsonException;
use function PHPUnit\Framework\isEmpty;

class TypeGetter
{
    public static function get($value, string $typeContext): string | ValidationType
    {
        if (is_array($value)) {
            return self::getArrayType($value, $typeContext);
        }

        if (is_string($value)) {
            return self::getTypeFromString($value, $typeContext);
        }

        if (is_numeric($value) && ! is_nan((float) $value)) {
            return PrimitiveType::NUMBER;
        }
//        if (value instanceof Date && DateTime.fromJSDate(value).isValid) return 'Date'; //TODO

        if (is_bool($value)) {
            return PrimitiveType::BOOLEAN;
        }

//        if (typeof value === 'object' && typeContext === 'Json') return 'Json'; //TODO

        return ValidationType::Null();
    }

    private static function getTypeFromString($value, string $typeContext): string
    {
        if (in_array($typeContext, [PrimitiveType::ENUM, PrimitiveType::STRING], true)) {
            return $typeContext;
        }

//        if (uuidValidate(value)) return 'Uuid'; TODO

        if (self::isValidDate($value)) {
            return self::getDateType($value);
        }

        if (self::isJson($value)) {
            return PrimitiveType::JSON;
        }

        if (self::isPoint($value, $typeContext)) {
            return PrimitiveType::POINT;
        }

        return PrimitiveType::STRING;
    }

    private static function isPoint(string $value, string $typeContext): bool
    {
        $potentialPoint = explode(',', $value);

        return count($potentialPoint) === 2 &&
            $typeContext === PrimitiveType::POINT &&
            self::get($potentialPoint, PrimitiveType::NUMBER) === ValidationType::Number();
    }

    private static function isJson(string $value): bool
    {
        try {
            return (bool) json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return false;
        }
    }

    private static function getArrayType(array $value, string $typeContext): ValidationType
    {
        if (isEmpty($value)) {
            return ValidationType::Empty();
        }

        if (self::isArrayOf(PrimitiveType::NUMBER, $value, $typeContext)) {
            return ValidationType::Number();
        }

        if (self::isArrayOf(PrimitiveType::UUID, $value, $typeContext)) {
            return ValidationType::Uuid();
        }

        if (self::isArrayOf(PrimitiveType::BOOLEAN, $value, $typeContext)) {
            return ValidationType::Boolean();
        }

        if (self::isArrayOf(PrimitiveType::STRING, $value, $typeContext)) {
            return ValidationType::String();
        }

        if (self::isArrayOf(PrimitiveType::ENUM, $value, $typeContext)) {
            return ValidationType::Enum();
        }

        return ValidationType::Null();
    }

    private static function isArrayOf(string $primitiveType, array $values, string $typeContext): bool
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

    private static function getDateType(string $value): String
    {
        $time = strtotime($value);

        if (date('Y-m-d', $time) === $value) {
            return PrimitiveType::DATEONLY;
        }

        if (date('H:i:s', $time) === $value) {
            return PrimitiveType::TIMEONLY;
        }

        return PrimitiveType::DATE;
    }
}
