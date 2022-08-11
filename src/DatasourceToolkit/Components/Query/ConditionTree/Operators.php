<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree;

class Operators
{
    public const UNIQUE_OPERATORS = [
        // All types besides arrays
        'Equal',
        'Not_Equal',
        'Less_Than',
        'Greater_Than',

        // Strings
        'Like',
        'Not_Contains',
        'Longer_Than',
        'Shorter_Than',

        // Arrays
        'Includes_All',
    ];

    public const INTERVAL_OPERATORS = [
        // Dates
        'Today',
        'Yesterday',
        'Previous_Month',
        'Previous_Quarter',
        'Previous_Week',
        'Previous_Year',
        'Previous_Month_To_Date',
        'Previous_Quarter_To_Date',
        'Previous_Week_To_Date',
        'Previous_X_Days_To_Date',
        'Previous_X_Days',
        'Previous_Year_To_Date',
    ];

    public const OTHER_OPERATORS = [
        // All types
        'Present',
        'Blank',
        'Missing',

        // All types besides arrays
        'In',
        'Not_In',

        // Strings
        'Starts_With',
        'Ends_With',
        'Contains',

        // Dates
        'Before',
        'After',
        'After_X_Hours_Ago',
        'Before_X_Hours_Ago',
        'Future',
        'Past',
    ];

    public const ALL_OPERATORS = [...self::UNIQUE_OPERATORS, ...self::INTERVAL_OPERATORS, ...self::OTHER_OPERATORS];
}
