<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree;

class Operators
{
    public const EQUAL = 'Equal';
    public const NOT_EQUAL = 'Not_Equal';
    public const LESS_THAN = 'Less_Than';
    public const GREATER_THAN = 'Greater_Than';
    public const MATCH = 'Match';
    public const LIKE = 'Like';
    public const ILIKE = 'ILike';
    public const NOT_CONTAINS = 'Not_Contains';
    public const CONTAINS = 'Contains';
    public const ICONTAINS = 'IContains';
    public const LONGER_THAN = 'Longer_Than';
    public const SHORTER_THAN = 'Shorter_Than';
    public const INCLUDES_ALL = 'Includes_All';
    public const PRESENT = 'Present';
    public const BLANK = 'Blank';
    public const IN = 'In';
    public const NOT_IN = 'Not_In';
    public const STARTS_WITH = 'Starts_With';
    public const ISTARTS_WITH = 'IStarts_With';
    public const ENDS_WITH = 'Ends_With';
    public const IENDS_WITH = 'IEnds_With';
    public const MISSING = 'Missing';
    public const BEFORE = 'Before';
    public const AFTER = 'After';
    public const AFTER_X_HOURS_AGO = 'After_X_Hours_Ago';
    public const BEFORE_X_HOURS_AGO = 'Before_X_Hours_Ago';
    public const FUTURE = 'Future';
    public const PAST = 'Past';
    public const TODAY = 'Today';
    public const YESTERDAY = 'Yesterday';
    public const PREVIOUS_WEEK = 'Previous_Week';
    public const PREVIOUS_MONTH = 'Previous_Month';
    public const PREVIOUS_QUARTER = 'Previous_Quarter';
    public const PREVIOUS_YEAR = 'Previous_Year';
    public const PREVIOUS_WEEK_TO_DATE = 'Previous_Week_To_Date';
    public const PREVIOUS_MONTH_TO_DATE = 'Previous_Month_To_Date';
    public const PREVIOUS_QUARTER_TO_DATE = 'Previous_Quarter_To_Date';
    public const PREVIOUS_YEAR_TO_DATE = 'Previous_Year_To_Date';
    public const PREVIOUS_X_DAYS = 'Previous_X_Days';
    public const PREVIOUS_X_DAYS_TO_DATE = 'Previous_X_Days_To_Date';

    public static function getIntervalOperators(): array
    {
        return [
            self::TODAY,
            self::YESTERDAY,
            self::PREVIOUS_MONTH,
            self::PREVIOUS_QUARTER,
            self::PREVIOUS_WEEK,
            self::PREVIOUS_YEAR,
            self::PREVIOUS_MONTH_TO_DATE,
            self::PREVIOUS_QUARTER_TO_DATE,
            self::PREVIOUS_WEEK_TO_DATE,
            self::PREVIOUS_X_DAYS_TO_DATE,
            self::PREVIOUS_X_DAYS,
            self::PREVIOUS_YEAR_TO_DATE,
        ];
    }

    public static function getAllOperators(): array
    {
        return array_values(
            (new \ReflectionClass(self::class))->getConstants()
        );
    }

    public static function getUniqueOperators(): array
    {
        return [
            self::EQUAL,
            self::NOT_EQUAL,
            self::LESS_THAN,
            self::GREATER_THAN,
            self::MATCH,
            self::NOT_CONTAINS,
            self::LONGER_THAN,
            self::SHORTER_THAN,
            self::INCLUDES_ALL,
        ];
    }
}
