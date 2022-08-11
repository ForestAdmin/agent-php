<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Collection;

class Rules
{
    public const BASE_OPERATORS = ['Blank', 'Equal', 'Missing', 'Not_Equal', 'Present'];

    public const ARRAY_OPERATORS = ['In', 'Not_In', 'Includes_All'];

    public const BASE_DATEONLY_OPERATORS = [
        'Today',
        'Yesterday',
        'Previous_X_Days_To_Date',
        'Previous_Week',
        'Previous_Week_To_Date',
        'Previous_Month',
        'Previous_Month_To_Date',
        'Previous_Quarter',
        'Previous_Quarter_To_Date',
        'Previous_Year',
        'Previous_Year_To_Date',
        'Past',
        'Future',
        'Previous_X_Days',
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
                    'Not_Contains',
                    'Ends_With',
                    'Starts_With',
                    'Longer_Than',
                    'Shorter_Than',
                    'Like',
                    'ILike',
                    'IContains',
                    'IEndsWith',
                    'IStartsWith',
                ],
                PrimitiveType::NUMBER   => [...self::BASE_OPERATORS, ...self::ARRAY_OPERATORS, 'Greater_Than', 'Less_Than'],
                PrimitiveType::DATEONLY => [...self::BASE_OPERATORS, ...self::BASE_DATEONLY_OPERATORS],
                PrimitiveType::DATE     => [...self::BASE_OPERATORS, ...self::BASE_DATEONLY_OPERATORS, 'Before_X_Hours_Ago', 'After_X_Hours_Ago'],
                PrimitiveType::TIMEONLY => [...self::BASE_OPERATORS, 'Less_Than', 'Greater_Than'],
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
                'In'                       => $validationTypesArray,
                'Not_In'                   => $validationTypesArray,
                'Includes_All'             => $validationTypesArray,
                'Blank'                    => $noTypeAllowed,
                'Missing'                  => $noTypeAllowed,
                'Present'                  => $noTypeAllowed,
                'Yesterday'                => $noTypeAllowed,
                'Today'                    => $noTypeAllowed,
                'Previous_Quarter'         => $noTypeAllowed,
                'Previous_Year'            => $noTypeAllowed,
                'Previous_Month'           => $noTypeAllowed,
                'Previous_Week'            => $noTypeAllowed,
                'Past'                     => $noTypeAllowed,
                'Future'                   => $noTypeAllowed,
                'Previous_Week_To_Date'    => $noTypeAllowed,
                'Previous_Month_To_Date'   => $noTypeAllowed,
                'Previous_Quarter_To_Date' => $noTypeAllowed,
                'Previous_Year_To_Date'    => $noTypeAllowed,
                'Previous_XDays_To_Date'   => ['Number'],
                'Previous_X_Days'          => ['Number'],
                'Before_X_Hours_Ago'       => ['Number'],
                'After_X_Hours_Ago'        => ['Number'],
            ]
        );

        return $operator ? $merged->get($operator) : $merged->all();
    }
}
