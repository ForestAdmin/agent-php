<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;

class FilterFactory
{
    public function getPreviousConditionTree(string $field, \DateTime $startPeriod, \DateTime $endPeriod)
    {
        $conditionTreeFactory = new ConditionTreeFactory();
        $conditionTreeFactory.intersect(
            // TODO CHECK FORMAT DATE OK ???
            new ConditionTreeLeaf($field, 'GreaterThan', $startPeriod->format('Y-m-d H:i:s')),
            new ConditionTreeLeaf($field, 'LessThan', $endPeriod->format('Y-m-d H:i:s'))
        );
    }


//    private static getPreviousPeriodByUnit(
//        field: string,
//        now: DateTime,
//        interval: string,
//      ): ConditionTree {
//        const dayBeforeYesterday = now.minus({ [interval]: 2 });
//
//    return this.getPreviousConditionTree(
//            field,
//            dayBeforeYesterday.startOf(interval as DateTimeUnit),
//          dayBeforeYesterday.endOf(interval as DateTimeUnit),
//        );
//      }
}
