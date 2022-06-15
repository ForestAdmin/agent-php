<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;

class FilterFactory
{
    public function getPreviousConditionTree(string $field, \DateTime $startPeriod, \DateTime $endPeriod)
    {
        $conditionTreeFactory = new ConditionTreeFactory();
        $conditionTreeFactory->intersect(
            // TODO CHECK FORMAT DATE OK ???
            new ConditionTreeLeaf($field, 'GreaterThan', $startPeriod->format('Y-m-d H:i:s')),
            new ConditionTreeLeaf($field, 'LessThan', $endPeriod->format('Y-m-d H:i:s'))
        );
    }

    public function getPreviousPeriodByUnit(string $field, \DateTime $now, int $interval = 2)
    {
        $dayBeforeYesterday = $now->sub(new \DateInterval('P' . $interval . 'D'));
        $startPeriod = \DateTime::createFromFormat('Y-m-d', $dayBeforeYesterday->format('Y-m-d'))->setTime(0, 0);
        $endPeriod = \DateTime::createFromFormat('Y-m-d', $dayBeforeYesterday->format('Y-m-d'))->setTime(23, 59, 59);

        return $this->getPreviousConditionTree($field, $startPeriod, $endPeriod);
    }



}
