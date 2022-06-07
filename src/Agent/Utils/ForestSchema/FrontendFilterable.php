<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Collection as IlluminateCollection;

final class FrontendFilterable
{
    public const BASE_OPERATORS = ['Equal', 'NotEqual', 'Present', 'Blank'];

    public const DATE_OPERATORS = [
        ...self::BASE_OPERATORS,
        'LessThan',
        'GreaterThan',
        'Today',
        'Yesterday',
        'PreviousXDays',
        'PreviousWeek',
        'PreviousQuarter',
        'PreviousYear',
        'PreviousXDaysToDate',
        'PreviousWeekToDate',
        'PreviousMonthToDate',
        'PreviousQuarterToDate',
        'PreviousYearToDate',
        'Past',
        'Future',
        'BeforeXHoursAgo',
        'AfterXHoursAgo',
    ];

    public const OPERATOR_BY_TYPE = [
        'Boolean'  => self::BASE_OPERATORS,
        'Date'     => self::DATE_OPERATORS,
        'Dateonly' => self::DATE_OPERATORS,
        'Enum'     => [...self::BASE_OPERATORS, 'In'],
        'Number'   => [...self::BASE_OPERATORS, 'In', 'GreaterThan', 'LessThan'],
        'String'   => [...self::BASE_OPERATORS, 'In', 'StartsWith', 'EndsWith', 'Contains', 'NotContains',],
        'Timeonly' => [...self::BASE_OPERATORS, 'GreaterThan', 'LessThan'],
        'Uuid'     => self::BASE_OPERATORS,
    ];

    /**
     * @param PrimitiveType|PrimitiveType[] $type
     */
    public static function isFilterable($type, array $operators = [])
    {
        $neededOperators = new IlluminateCollection(self::getRequiredOperators($type->value));
        $supportedOperators = new IlluminateCollection($operators);

        // TODO SHOULD BE THE OPPOSITE ? CHECK SUPPORTED INTO NEEDED ?
        return $neededOperators->isNotEmpty() && $neededOperators->every(fn ($operator) => $supportedOperators->contains($operator));
    }

    /**
     * @param PrimitiveType|PrimitiveType[] $type
     * @return mixed|string[]|null
     */
    public static function getRequiredOperators($type)
    {
        if (is_string($type) && in_array($type, array_keys(self::OPERATOR_BY_TYPE), true)) {
            return self::OPERATOR_BY_TYPE[$type];
        }

        // It sound highly unlikely that this operator can work with dates, or nested objects
        // and they should be more restricted, however the frontend code does not seems to check the
        // array's content so I'm replicating the same test here
        if (is_array($type)) {
            return ['IncludesAll'];
        }

        return null;
    }
}
