<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

final class FrontendFilterable
{
    public const BASE_OPERATORS = [
        Operators::EQUAL, Operators::NOT_EQUAL, Operators::PRESENT, Operators::BLANK,
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
        Operators::BEFORE_X_HOURS_AGO,
        Operators::AFTER_X_HOURS_AGO,
        Operators::BEFORE,
        Operators::AFTER,
    ];

    public const DATE_OPERATORS = [
        ...self::BASE_OPERATORS,
        ...self::BASE_DATEONLY_OPERATORS,
    ];

    public const OPERATOR_BY_TYPE = [
        'Binary'   => self::BASE_OPERATORS,
        'Boolean'  => self::BASE_OPERATORS,
        'Date'     => self::DATE_OPERATORS,
        'Dateonly' => self::DATE_OPERATORS,
        'Uuid'     => self::BASE_OPERATORS,
        'Enum'     => [...self::BASE_OPERATORS, Operators::IN],
        'Number'   => [...self::BASE_OPERATORS, Operators::IN, Operators::GREATER_THAN, Operators::LESS_THAN],
        'Timeonly' => [...self::BASE_OPERATORS, Operators::GREATER_THAN, Operators::LESS_THAN],
        'String'   => [
            ...self::BASE_OPERATORS,
            Operators::IN,
            Operators::STARTS_WITH,
            Operators::ENDS_WITH,
            Operators::CONTAINS,
            Operators::NOT_CONTAINS,
        ],
        'Json'     => [],
    ];

    public static function isFilterable(array $operator): bool
    {
        return $operator && ! empty($operator);
    }
}
