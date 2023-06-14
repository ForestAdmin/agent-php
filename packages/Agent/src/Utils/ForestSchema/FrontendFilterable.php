<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Collection as IlluminateCollection;

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
