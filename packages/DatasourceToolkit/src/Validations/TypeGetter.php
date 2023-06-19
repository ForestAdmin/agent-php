<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Str;
use JsonException;

class TypeGetter
{
    public static function get($value, ?string $typeContext = null): ?string
    {
        if ($typeContext === PrimitiveType::JSON) {
            return PrimitiveType::JSON;
        }
        if (is_string($value)) {
            return self::getTypeFromString($value, $typeContext);
        }

        if (is_numeric($value) && ! is_nan((float) $value)) {
            return PrimitiveType::NUMBER;
        }

        if ($value instanceof \DateTime) {
            return PrimitiveType::DATE;
        }

        if (is_bool($value)) {
            return PrimitiveType::BOOLEAN;
        }

        return null;
    }

    private static function getTypeFromString($value, ?string $typeContext = null): string
    {
        if (in_array($typeContext, [PrimitiveType::ENUM, PrimitiveType::STRING], true)) {
            return $typeContext;
        }

        if (Str::isUuid($value)) {
            return PrimitiveType::UUID;
        }

        if (self::isValidDate($value) && in_array($typeContext, [PrimitiveType::DATE, PrimitiveType::DATEONLY, PrimitiveType::TIMEONLY], true)) {
            return self::getDateType($value);
        }

        if (is_numeric($value) && $typeContext === PrimitiveType::NUMBER) {
            return PrimitiveType::NUMBER;
        }

        if (self::isJson($value)) {
            return PrimitiveType::JSON;
        }

        if (self::isPoint($value, $typeContext)) {
            return PrimitiveType::POINT;
        }

        return PrimitiveType::STRING;
    }

    private static function isPoint(string $value, ?string $typeContext = null): bool
    {
        $potentialPoint = explode(',', $value);

        return count($potentialPoint) === 2 &&
            $typeContext === PrimitiveType::POINT &&
            collect($potentialPoint)->every(function ($point) {
                return is_numeric($point)
                    && self::get((float) $point, PrimitiveType::NUMBER) === PrimitiveType::NUMBER;
            });
    }

    /**
     * @throws \JsonException
     */
    private static function isJson($value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return true;
    }

    private static function isArrayOf(string $primitiveType, array $values, ?string $typeContext = null): bool
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
