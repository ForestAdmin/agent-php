<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters;

use DateTime;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FilterFactory
{
    /**
     * @throws \Exception
     */
    public static function getPreviousPeriodFilter(Filter $filter, string $timezone): Filter
    {
        return $filter->override(
            conditionTree: $filter->getConditionTree()->replaceLeafs(
                fn (ConditionTreeLeaf $leaf) => match ($leaf->getOperator()) {
                    'Yesterday'              => self::getPreviousPeriodByUnit($leaf->getField(), 'Day', $timezone),
                    'PreviousWeek'           => self::getPreviousPeriodByUnit($leaf->getField(), 'Week', $timezone),
                    'PreviousMonth'          => self::getPreviousPeriodByUnit($leaf->getField(), 'Month', $timezone),
                    'PreviousQuarter'        => self::getPreviousPeriodByUnit($leaf->getField(), 'Quarter', $timezone),
                    'PreviousYear'           => self::getPreviousPeriodByUnit($leaf->getField(), 'Year', $timezone),
                    'PreviousWeekToDate'     => $leaf->override(operator: 'PreviousWeek'),
                    'PreviousMonthToDate'    => $leaf->override(operator: 'PreviousMonth'),
                    'PreviousQuarterToDate'  => $leaf->override(operator: 'PreviousQuarter'),
                    'PreviousYearToDate'     => $leaf->override(operator: 'PreviousYear'),
                    'Today'                  => $leaf->override(operator: 'Yesterday'),
                    'PreviousXDays'          => self::getPreviousXDaysPeriod($leaf, $timezone, 'PreviousXDays'),
                    'PreviousXDaysToDate'    => self::getPreviousXDaysPeriod($leaf, $timezone, 'PreviousXDaysToDate'),
                    default                  => $leaf
                }
            )
        );
    }

    public static function getPreviousConditionTree(string $field, DateTime $startPeriod, DateTime $endPeriod): ConditionTree
    {
        return ConditionTreeFactory::intersect(
            [
                new ConditionTreeLeaf($field, 'GreaterThan', $startPeriod->format('Y-m-d H:i:s')),
                new ConditionTreeLeaf($field, 'LessThan', $endPeriod->format('Y-m-d H:i:s')),
            ]
        );
    }

    /**
     * @throws \Exception
     */
    private static function getPreviousPeriodByUnit(string $field, string $unit, string $timezone): ConditionTree
    {
        $allowedUnit = [
            'Day',
            'Week',
            'Month',
            'Quarter',
            'Year',
        ];

        if (! in_array($unit, $allowedUnit)) {
            throw new ForestException('Operator not allowed');
        }

        $sub = 'sub' . Str::plural($unit);
        $start = 'startOf' . $unit;
        $end = 'endOf' . $unit;
        $startPeriod = Carbon::now($timezone)->$sub(2)->$start();
        $endPeriod = Carbon::now($timezone)->$sub(2)->$end();

        return self::getPreviousConditionTree($field, $startPeriod->toDateTime(), $endPeriod->toDateTime());
    }

    private static function getPreviousXDaysPeriod(ConditionTreeLeaf $leaf, string $timezone, string $operator): ConditionTree
    {
        $startPeriod = Carbon::now($timezone)->subDays(2 * $leaf->getValue())->startOfDay();
        $endPeriod = $operator === 'PreviousXDays'
            ? Carbon::now($timezone)->subDays($leaf->getValue())->startOfDay()
            : Carbon::now($timezone)->subDays($leaf->getValue());

        return self::getPreviousConditionTree($leaf->getField(), $startPeriod->toDateTime(), $endPeriod->toDateTime());
    }
}
