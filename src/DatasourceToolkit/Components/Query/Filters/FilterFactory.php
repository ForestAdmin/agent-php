<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters;

use DateTime;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
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

    public static function makeThroughFilter(Collection $collection, array $id, string $relationName, Caller $caller, Filter $baseForeignFilter): Filter
    {
        $relation = $collection->getFields()[$relationName];
        $originValue = CollectionUtils::getValue($collection, $caller, $id, $relation->getOriginKeyTarget());
        $foreignRelation = CollectionUtils::getThroughTarget($collection, $relationName);

        // Optimization for many to many when there is not search/segment (saves one query)
        if ($foreignRelation && $baseForeignFilter->isNestable()) {
            $baseThroughFilter = $baseForeignFilter->nest($foreignRelation);

            return $baseThroughFilter->override(
                conditionTree: ConditionTreeFactory::intersect(
                    [
                        new ConditionTreeLeaf($relation->getOriginKey(), 'Equal', $originValue),
                        new ConditionTreeLeaf($relation->getForeignKey(), 'Present'),
                        $baseThroughFilter->getConditionTree(),
                    ]
                )
            );
        }

        // Otherwise we have no choice but to call the target collection so that search and segment
        // are correctly apply, and then match ids in the though collection.
        /** @var Collection $target */
        $target = AgentFactory::get('datasource')->getCollection($relation->getForeignCollection());
        $records = $target->list(
            self::makeForeignFilter(
                $collection,
                $id,
                $relationName,
                $caller,
                $baseForeignFilter
            ),
            new Projection([$relation->getForeignKeyTarget()])
        );

        return new Filter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    // only children of parent
                    new ConditionTreeLeaf($relation->getOriginKey(), 'Equal', $originValue),
                    // only the children which match the conditions in baseForeignFilter
                    new ConditionTreeLeaf(
                        $relation->getForeignKey(),
                        'In',
                        collect($records)
                            ->map(fn ($record) => $collection->toArray($record)[$relation->getForeignKeyTarget()])
                            ->toArray()
                    ),
                ]
            )
        );
    }

    /**
     * Given a collection and a OneToMany/ManyToMany relation, generate a filter which
     * - match only children of the provided recordId
     * - can apply on the target collection of the relation
     */
    public static function makeForeignFilter(Collection $collection, array $id, string $relationName, Caller $caller, Filter $baseForeignFilter): Filter
    {
        $relation = SchemaUtils::getToManyRelation($collection, $relationName);
        $originValue = CollectionUtils::getValue($collection, $caller, $id, $relation->getOriginKeyTarget());

        if ($relation->getType() === 'OneToMany') {
            $originTree = new ConditionTreeLeaf($relation->getOriginKey(), 'Equal', $originValue);
        } else {
            // ManyToMany case
            /** @var Collection $through */
            $through = AgentFactory::get('datasource')->getCollection($relation->getThroughCollection());
            $throughTree = ConditionTreeFactory::intersect(
                [
                    new ConditionTreeLeaf($relation->getOriginKey(), 'Equal', $originValue),
                    new ConditionTreeLeaf($relation->getForeignKey(), 'Present'),
                ]
            );
            $records = $through->list(new Filter(conditionTree: $throughTree), new Projection([$relation->getForeignKey()]));
//            dd($collection->toArray($records[0])[$relation->getForeignKey()]);
            $originTree = new ConditionTreeLeaf(
                $relation->getForeignKeyTarget(),
                'In',
                collect($records)
                    ->map(fn ($record) => $collection->toArray($record)[$relation->getForeignKey()])
                    ->toArray()
            );
        }

        return $baseForeignFilter->override(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    $baseForeignFilter->getConditionTree(), $originTree,
                ]
            )
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
