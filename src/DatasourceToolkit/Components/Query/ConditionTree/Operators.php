<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree;

class Operators
{
    public const UNIQUE_OPERATORS = [
        // All types besides arrays
        'Equal',
        'NotEqual',
        'LessThan',
        'GreaterThan',

        // Strings
        'Like',
        'NotContains',
        'LongerThan',
        'ShorterThan',

        // Arrays
        'IncludesAll',
    ];

    public const INTERVAL_OPERATORS = [
        // Dates
        'Today',
        'Yesterday',
        'PreviousMonth',
        'PreviousQuarter',
        'PreviousWeek',
        'PreviousYear',
        'PreviousMonthToDate',
        'PreviousQuarterToDate',
        'PreviousWeekToDate',
        'PreviousXDaysToDate',
        'PreviousXDays',
        'PreviousYearToDate',
    ];

    public const OTHER_OPERATORS = [
        // All types
        'Present',
        'Blank',
        'Missing',

        // All types besides arrays
        'In',
        'NotIn',

        // Strings
        'StartsWith',
        'EndsWith',
        'Contains',

        // Dates
        'Before',
        'After',
        'AfterXHoursAgo',
        'BeforeXHoursAgo',
        'Future',
        'Past',
    ];

    public const allOperators = [...self::UNIQUE_OPERATORS, ...self::INTERVAL_OPERATORS, ...self::OTHER_OPERATORS];
}
