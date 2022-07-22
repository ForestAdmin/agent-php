<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Collection;

class Rules
{
    public const BASE_OPERATORS = ['Blank', 'Equal', 'Missing', 'NotEqual', 'Present'];

    public const ARRAY_OPERATORS = ['In', 'NotIn', 'IncludesAll'];

    public const BASE_DATEONLY_OPERATORS = [
        'Today',
        'Yesterday',
        'PreviousXDaysToDate',
        'PreviousWeek',
        'PreviousWeekToDate',
        'PreviousMonth',
        'PreviousMonthToDate',
        'PreviousQuarter',
        'PreviousQuarterToDate',
        'PreviousYear',
        'PreviousYearToDate',
        'Past',
        'Future',
        'PreviousXDays',
        'Before',
        'After',
    ];

    public static function getAllowedOperatorsForColumnType(?string $primitiveType = null): array
    {
        $allowedOperators = collect(
            [
                PrimitiveType::STRING   => [
                    ...self::BASE_OPERATORS,
                    ...self::ARRAY_OPERATORS,
                    'Contains',
                    'NotContains',
                    'EndsWith',
                    'StartsWith',
                    'LongerThan',
                    'ShorterThan',
                    'Like',
                    'ILike',
                    'IContains',
                    'IEndsWith',
                    'IStartsWith',
                ],
                PrimitiveType::NUMBER   => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS, 'GreaterThan', 'LessThan'],
                PrimitiveType::DATEONLY => [...self::BASE_OPERATORS, ...self::BASE_DATEONLY_OPERATORS],
                PrimitiveType::DATE     => [...self::BASE_OPERATORS, ...self::BASE_DATEONLY_OPERATORS, 'BeforeXHoursAgo', 'AfterXHoursAgo'],
                PrimitiveType::TIMEONLY => [...self::BASE_OPERATORS, 'LessThan', 'GreaterThan'],
                PrimitiveType::ENUM     => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS],
                PrimitiveType::UUID     => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS],
                PrimitiveType::JSON     => ['Blank', 'Missing', 'Present'],
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
                'In'                    => $validationTypesArray,
                'NotIn'                 => $validationTypesArray,
                'IncludesAll'           => $validationTypesArray,
                'Blank'                 => $noTypeAllowed,
                'Missing'               => $noTypeAllowed,
                'Present'               => $noTypeAllowed,
                'Yesterday'             => $noTypeAllowed,
                'Today'                 => $noTypeAllowed,
                'PreviousQuarter'       => $noTypeAllowed,
                'PreviousYear'          => $noTypeAllowed,
                'PreviousMonth'         => $noTypeAllowed,
                'PreviousWeek'          => $noTypeAllowed,
                'Past'                  => $noTypeAllowed,
                'Future'                => $noTypeAllowed,
                'PreviousWeekToDate'    => $noTypeAllowed,
                'PreviousMonthToDate'   => $noTypeAllowed,
                'PreviousQuarterToDate' => $noTypeAllowed,
                'PreviousYearToDate'    => $noTypeAllowed,
                'PreviousXDaysToDate'   => ['Number'],
                'PreviousXDays'         => ['Number'],
                'BeforeXHoursAgo'       => ['Number'],
                'AfterXHoursAgo'        => ['Number'],
            ]
        );

        return $operator ? $merged->get($operator) : $merged->all();
    }
}
