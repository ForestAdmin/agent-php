<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Collection as IlluminateCollection;

final class FrontendFilterable
{
    public const BASE_OPERATORS = ['Equal', 'Not_Equal', 'Present', 'Blank'];

    public const BASE_DATEONLY_OPERATORS = [
        'Today',
        'Yesterday',
        'Previous_X_Days',
        'Previous_Week',
        'Previous_Month',
        'Previous_Quarter',
        'Previous_Year',
        'Previous_X_Days_To_Date',
        'Previous_Week_To_Date',
        'Previous_Month_To_Date',
        'Previous_Quarter_To_Date',
        'Previous_Year_To_Date',
        'Past',
        'Future',
        'Before_X_Hours_Ago',
        'After_X_Hours_Ago',
        'Before',
        'After',
    ];

    public const DATE_OPERATORS = [
        ...self::BASE_OPERATORS,
        ...self::BASE_DATEONLY_OPERATORS,
    ];

    public const OPERATOR_BY_TYPE = [
        'Boolean'  => self::BASE_OPERATORS,
        'Date'     => self::DATE_OPERATORS,
        'Dateonly' => self::DATE_OPERATORS,
        'Enum'     => [...self::BASE_OPERATORS, 'In'],
        'Number'   => [...self::BASE_OPERATORS, 'In', 'Greater_Than', 'Less_Than'],
        'String'   => [...self::BASE_OPERATORS, 'In', 'Starts_With', 'Ends_With', 'Contains', 'Not_Contains'],
        'Timeonly' => [...self::BASE_OPERATORS, 'Greater_Than', 'Less_Than'],
        'Uuid'     => self::BASE_OPERATORS,
    ];

//    public static function allOperators(): array
//    {
//        return [
//            ...self::BASE_OPERATORS,
//            ...self::DATE_OPERATORS,
//            ...[
//                'In',
//                'GreaterThan',
//                'LessThan',
//                'StartsWith',
//                'EndsWith',
//                'Contains',
//                'NotContains',
//            ],
//        ];
//    }

    public static function isFilterable(string|array $type, array $operators = []): bool
    {
        $neededOperators = new IlluminateCollection(self::getRequiredOperators($type));
        $supportedOperators = new IlluminateCollection($operators);

        return $neededOperators->isNotEmpty() && $neededOperators->every(fn ($operator) => $supportedOperators->contains($operator));
    }

    /**
     * @param PrimitiveType|PrimitiveType[] $type
     * @return mixed|string[]|null
     */
    public static function getRequiredOperators(string|array $type)
    {
        if (is_string($type) && in_array($type, array_keys(self::OPERATOR_BY_TYPE), true)) {
            return self::OPERATOR_BY_TYPE[$type];
        }

        // It sound highly unlikely that this operator can work with dates, or nested objects
        // and they should be more restricted, however the frontend code does not seems to check the
        // array's content so I'm replicating the same test here
        if (is_array($type)) {
            return ['Includes_All'];
        }

        return null;
    }
}
