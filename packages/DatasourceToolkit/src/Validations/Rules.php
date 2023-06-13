<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
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
        $allowedTypes = [
            PrimitiveType::STRING   => [PrimitiveType::STRING, null],
            PrimitiveType::NUMBER   => [PrimitiveType::NUMBER, null],
            PrimitiveType::DATEONLY => [PrimitiveType::DATEONLY, null],
            PrimitiveType::DATE     => [PrimitiveType::DATE, null],
            PrimitiveType::TIMEONLY => [PrimitiveType::TIMEONLY, null],
            PrimitiveType::ENUM     => [PrimitiveType::ENUM, null],
            PrimitiveType::UUID     => [PrimitiveType::UUID, null],
            PrimitiveType::JSON     => [PrimitiveType::JSON, null],
            PrimitiveType::BOOLEAN  => [PrimitiveType::BOOLEAN, null],
            PrimitiveType::POINT    => [PrimitiveType::POINT, null],
        ];

        return $primitiveType ? $allowedTypes[$primitiveType] : $allowedTypes;
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
        $noTypeAllowed = [null];
        $allowedTypes = collect(self::computeAllowedTypesForOperators());
        $merged = $allowedTypes->merge(
            [
                Operators::IN                       => array_merge($allowedTypes->get(Operators::IN), [null]),
                Operators::NOT_IN                   => array_merge($allowedTypes->get(Operators::NOT_IN), [null]),
                Operators::INCLUDES_ALL             => array_merge($allowedTypes->get(Operators::INCLUDES_ALL), [null]),
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
