<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Collection;
use mysql_xdevapi\Exception;

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

    public static function getAllowedOperatorsForColumnType(?string $primitiveType = null): Collection|array
    {
        $allowedOperators = collect(
            [
                PrimitiveType::String()->value   => [
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
                PrimitiveType::Number()->value   => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS, 'GreaterThan', 'LessThan'],
                PrimitiveType::Dateonly()->value => [...self::BASE_OPERATORS, ...self::BASE_DATEONLY_OPERATORS],
                PrimitiveType::Date()->value     => [...self::BASE_OPERATORS, ...self::BASE_DATEONLY_OPERATORS, 'BeforeXHoursAgo', 'AfterXHoursAgo'],
                PrimitiveType::Timeonly()->value => [...self::BASE_OPERATORS, 'LessThan', 'GreaterThan'],
                PrimitiveType::Enum()->value     => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS],
                PrimitiveType::Uuid()->value     => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS],
                PrimitiveType::Json()->value     => ['Blank', 'Missing', 'Present'],
                PrimitiveType::Boolean()->value  => self::BASE_OPERATORS,
                PrimitiveType::Point()->value    => self::BASE_OPERATORS,
            ]
        );

        return $primitiveType ? $allowedOperators->get($primitiveType) : $allowedOperators->toArray();
    }

    public static function getAllowedTypesForColumnType(?string $primitiveType = null): Collection|array
    {
        $allowedTypes = collect(
            [
                PrimitiveType::String()->value   => [PrimitiveType::String(), ValidationType::String(), ValidationType::Null()],
                PrimitiveType::Number()->value   => [PrimitiveType::Number(), ValidationType::Number(), ValidationType::Null()],
                PrimitiveType::Dateonly()->value => [PrimitiveType::Dateonly(), PrimitiveType::Number(), ValidationType::Null()],
                PrimitiveType::Date()->value     => [PrimitiveType::Date(), PrimitiveType::Number(), ValidationType::Null()],
                PrimitiveType::Timeonly()->value => [PrimitiveType::Timeonly(), ValidationType::Null()],
                PrimitiveType::Enum()->value     => [PrimitiveType::Enum(), ValidationType::Enum(), ValidationType::Null()],
                PrimitiveType::Uuid()->value     => [PrimitiveType::Uuid(), ValidationType::Uuid(), ValidationType::Null()],
                PrimitiveType::Json()->value     => [PrimitiveType::Json(), ValidationType::Null()],
                PrimitiveType::Boolean()->value  => [PrimitiveType::Boolean(), ValidationType::Boolean(), ValidationType::Null()],
                PrimitiveType::Point()->value    => [PrimitiveType::Point(), ValidationType::Null()],
            ]
        );

        return $primitiveType ? $allowedTypes->get($primitiveType) : $allowedTypes->toArray();
    }


    private static function computeAllowedTypesForOperators()
    {
        return self::getAllowedOperatorsForColumnType()->keys()->reduce(
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
        $noTypeAllowed = [ValidationType::Null()->value];
        $validationTypesArray = array_values(ValidationType::toArray());

        $allowedTypes = collect(self::computeAllowedTypesForOperators());
        $merged = $allowedTypes->merge(
            [
                'In'=> $validationTypesArray,
                'NotIn'=> $validationTypesArray,
                'IncludesAll'=>$validationTypesArray,
                'Blank'=> $noTypeAllowed,
                'Missing'=> $noTypeAllowed,
                'Present'=> $noTypeAllowed,
                'Yesterday'=> $noTypeAllowed,
                'Today'=> $noTypeAllowed,
                'PreviousQuarter'=> $noTypeAllowed,
                'PreviousYear'=> $noTypeAllowed,
                'PreviousMonth'=> $noTypeAllowed,
                'PreviousWeek'=> $noTypeAllowed,
                'Past'=> $noTypeAllowed,
                'Future'=> $noTypeAllowed,
                'PreviousWeekToDate'=> $noTypeAllowed,
                'PreviousMonthToDate'=> $noTypeAllowed,
                'PreviousQuarterToDate'=> $noTypeAllowed,
                'PreviousYearToDate'=> $noTypeAllowed,
                'PreviousXDaysToDate'=> ['Number'],
                'PreviousXDays'=> ['Number'],
                'BeforeXHoursAgo'=> ['Number'],
                'AfterXHoursAgo'=> ['Number'],
            ]
        );

        return $operator ? $merged->get($operator) : $merged->all();
    }
}
