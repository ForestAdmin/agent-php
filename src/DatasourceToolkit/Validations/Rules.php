<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Collection;

class Rules
{
    public const BASE_OPERATORS = [
        Operators::EQUAL,
        Operators::NOT_EQUAL,
        Operators::PRESENT,
        Operators::BLANK,
        Operators::MISSING,
    ];

    public const ARRAY_OPERATORS = [
        Operators::IN,
        Operators::NOT_IN,
        Operators::INCLUDES_ALL,
    ];

    public const BASE_DATEONLY_OPERATORS = [
        Operators::TODAY,
        Operators::YESTERDAY,
        Operators::PREVIOUS_X_DAYS,
        Operators::PREVIOUS_WEEK,
        Operators::PREVIOUS_MONTH,
        Operators::PREVIOUS_QUARTER,
        Operators::PREVIOUS_YEAR,
        Operators::PREVIOUS_X_DAYS_TO_DATE,
        Operators::PREVIOUS_WEEK_TO_DATE,
        Operators::PREVIOUS_MONTH_TO_DATE,
        Operators::PREVIOUS_QUARTER_TO_DATE,
        Operators::PREVIOUS_YEAR_TO_DATE,
        Operators::PAST,
        Operators::FUTURE,
        Operators::BEFORE,
        Operators::AFTER,
    ];

    public static function getAllowedOperatorsForColumnType(?string $primitiveType = null): array
    {
        $allowedOperators = collect(
            [
                PrimitiveType::STRING   => [
                    ...self::BASE_OPERATORS,
                    ...self::ARRAY_OPERATORS,
                    Operators::CONTAINS,
                    Operators::NOT_CONTAINS,
                    Operators::ENDS_WITH,
                    Operators::STARTS_WITH,
                    Operators::LONGER_THAN,
                    Operators::SHORTER_THAN,
                    Operators::LIKE,
                    Operators::ILIKE,
                    Operators::ICONTAINS,
                    Operators::IENDS_WITH,
                    Operators::ISTARTS_WITH,
                ],
                PrimitiveType::NUMBER   => [
                    ...self::BASE_OPERATORS,
                    ...self::ARRAY_OPERATORS,
                    Operators::GREATER_THAN,
                    Operators::LESS_THAN,
                ],
                PrimitiveType::DATE     => [
                    ...self::BASE_OPERATORS,
                    ...self::BASE_DATEONLY_OPERATORS,
                    Operators::BEFORE_X_HOURS_AGO,
                    Operators::AFTER_X_HOURS_AGO,
                ],
                PrimitiveType::TIMEONLY => [
                    ...self::BASE_OPERATORS,
                    Operators::LESS_THAN,
                    Operators::GREATER_THAN,
                ],
                PrimitiveType::JSON     => [
                    Operators::BLANK,
                    Operators::MISSING,
                    Operators::PRESENT,
                ],
                PrimitiveType::DATEONLY => [...self::BASE_OPERATORS, ...self::BASE_DATEONLY_OPERATORS],
                PrimitiveType::ENUM     => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS],
                PrimitiveType::UUID     => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS],
                PrimitiveType::BOOLEAN  => self::BASE_OPERATORS,
                PrimitiveType::POINT    => self::BASE_OPERATORS,
            ]
        );

        return $primitiveType ? $allowedOperators->get($primitiveType) : $allowedOperators->toArray();
    }

    public static function getAllowedTypesForColumnType(?string $primitiveType = null): Collection|array
    {
        $allowedTypes = collect(
            [
                PrimitiveType::STRING   => [PrimitiveType::STRING, ArrayType::String(), ArrayType::Null()],
                PrimitiveType::NUMBER   => [PrimitiveType::NUMBER, ArrayType::Number(), ArrayType::Null()],
                PrimitiveType::DATEONLY => [PrimitiveType::DATEONLY, PrimitiveType::NUMBER, ArrayType::Null()],
                PrimitiveType::DATE     => [PrimitiveType::DATE, PrimitiveType::NUMBER, ArrayType::Null()],
                PrimitiveType::TIMEONLY => [PrimitiveType::TIMEONLY, ArrayType::Null()],
                PrimitiveType::ENUM     => [PrimitiveType::ENUM, ArrayType::Enum(), ArrayType::Null()],
                PrimitiveType::UUID     => [PrimitiveType::UUID, ArrayType::Uuid(), ArrayType::Null()],
                PrimitiveType::JSON     => [PrimitiveType::JSON, ArrayType::Null()],
                PrimitiveType::BOOLEAN  => [PrimitiveType::BOOLEAN, ArrayType::Boolean(), ArrayType::Null()],
                PrimitiveType::POINT    => [PrimitiveType::POINT, ArrayType::Null()],
            ]
        );

        return $primitiveType ? $allowedTypes->get($primitiveType) : $allowedTypes->toArray();
    }

    private static function computeAllowedTypesForOperators()
    {
        return collect(self::getAllowedOperatorsForColumnType())->keys()->reduce(
            static function ($result, $type) {
                $allowedOperators = self::getAllowedOperatorsForColumnType($type);
                foreach ($allowedOperators as $operator) {
                    if (isset($result[$operator])) {
                        $result[$operator][] = $type;
                    } else {
                        $result[$operator] = [$type];
                    }
                }

                return $result;
            }
        );
    }

    public static function getAllowedTypesForOperator(?string $operator = null): array
    {
        $noTypeAllowed = [ArrayType::Null()->value];
        $validationTypesArray = array_values(ArrayType::toArray());

        $allowedTypes = collect(self::computeAllowedTypesForOperators());
        $merged = $allowedTypes->merge(
            [
                Operators::IN                       => $validationTypesArray,
                Operators::NOT_IN                   => $validationTypesArray,
                Operators::INCLUDES_ALL             => $validationTypesArray,
                Operators::BLANK                    => $noTypeAllowed,
                Operators::MISSING                  => $noTypeAllowed,
                Operators::PRESENT                  => $noTypeAllowed,
                Operators::YESTERDAY                => $noTypeAllowed,
                Operators::TODAY                    => $noTypeAllowed,
                Operators::PREVIOUS_QUARTER         => $noTypeAllowed,
                Operators::PREVIOUS_YEAR            => $noTypeAllowed,
                Operators::PREVIOUS_MONTH           => $noTypeAllowed,
                Operators::PREVIOUS_WEEK            => $noTypeAllowed,
                Operators::PAST                     => $noTypeAllowed,
                Operators::FUTURE                   => $noTypeAllowed,
                Operators::PREVIOUS_WEEK_TO_DATE    => $noTypeAllowed,
                Operators::PREVIOUS_MONTH_TO_DATE   => $noTypeAllowed,
                Operators::PREVIOUS_QUARTER_TO_DATE => $noTypeAllowed,
                Operators::PREVIOUS_YEAR_TO_DATE    => $noTypeAllowed,
                Operators::PREVIOUS_X_DAYS_TO_DATE  => ['Number'],
                Operators::PREVIOUS_X_DAYS          => ['Number'],
                Operators::BEFORE_X_HOURS_AGO       => ['Number'],
                Operators::AFTER_X_HOURS_AGO        => ['Number'],
            ]
        );

        return $operator ? $merged->get($operator) : $merged->all();
    }
}
