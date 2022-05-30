<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Query\ConditionTree;

class Operators
{
    public const uniqueOperators = [
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

    public const intervalOperators = [
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

    public const otherOperators = [
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

    public const allOperators = [...self::uniqueOperators, ...self::intervalOperators, ...self::otherOperators];
}
