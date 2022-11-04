<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters;

use DateTime;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
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
                    Operators::YESTERDAY                => self::getPreviousPeriodByUnit($leaf->getField(), 'Day', $timezone),
                    Operators::PREVIOUS_WEEK            => self::getPreviousPeriodByUnit($leaf->getField(), 'Week', $timezone),
                    Operators::PREVIOUS_MONTH           => self::getPreviousPeriodByUnit($leaf->getField(), 'Month', $timezone),
                    Operators::PREVIOUS_QUARTER         => self::getPreviousPeriodByUnit($leaf->getField(), 'Quarter', $timezone),
                    Operators::PREVIOUS_YEAR            => self::getPreviousPeriodByUnit($leaf->getField(), 'Year', $timezone),
                    Operators::PREVIOUS_WEEK_TO_DATE    => $leaf->override(operator: Operators::PREVIOUS_WEEK),
                    Operators::PREVIOUS_MONTH_TO_DATE   => $leaf->override(operator: Operators::PREVIOUS_MONTH),
                    Operators::PREVIOUS_QUARTER_TO_DATE => $leaf->override(operator: Operators::PREVIOUS_QUARTER),
                    Operators::PREVIOUS_YEAR_TO_DATE    => $leaf->override(operator: Operators::PREVIOUS_YEAR),
                    Operators::TODAY                    => $leaf->override(operator: Operators::YESTERDAY),
                    Operators::PREVIOUS_X_DAYS          => self::getPreviousXDaysPeriod($leaf, $timezone, 'Previous_X_Days'),
                    Operators::PREVIOUS_X_DAYS_TO_DATE  => self::getPreviousXDaysPeriod($leaf, $timezone, 'Previous_X_Days_To_Date'),
                    default                             => $leaf
                }
            )
        );
    }

    public static function getPreviousConditionTree(string $field, DateTime $startPeriod, DateTime $endPeriod): ConditionTree
    {
        return ConditionTreeFactory::intersect(
            [
                new ConditionTreeLeaf($field, Operators::GREATER_THAN, $startPeriod->format('Y-m-d H:i:s')),
                new ConditionTreeLeaf($field, Operators::LESS_THAN, $endPeriod->format('Y-m-d H:i:s')),
            ]
        );
    }

    public static function makeThroughFilter(Collection $collection, array $id, string $relationName, Caller $caller, Filter $baseForeignFilter): Filter
    {
        $relation = $collection->getFields()[$relationName];
        $originValue = CollectionUtils::getValue($collection, $caller, $id, $relation->getOriginKeyTarget());

        return $baseForeignFilter->override(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    new ConditionTreeLeaf($relation->getInverseRelationName() . ':' . $relation->getOriginKeyTarget(), Operators::EQUAL, $originValue),
                    $baseForeignFilter->getConditionTree(),
                ]
            )
        );
    }

    /**
     * Given a collection and a OneToMany/ManyToMany relation, generate a filter which
     * - match only children of the provided recordId
     * - can apply on the target collection of the relation
     */
    // todo check this method - it's useful for our agent ?
    public static function makeForeignFilter(CollectionContract $collection, array $id, string $relationName, Caller $caller, Filter $baseForeignFilter): Filter
    {
        $relation = SchemaUtils::getToManyRelation($collection, $relationName);
        $originValue = CollectionUtils::getValue($collection, $caller, $id, $relation->getOriginKeyTarget());
        if ($relation->getType() === 'OneToMany') {
            $originTree = new ConditionTreeLeaf($relation->getInverseRelationName(), Operators::EQUAL, $originValue);
        } else {
            // ManyToMany case
            // todo useful ?
            /** @var Collection $foreignCollection */
            $foreignCollection = AgentFactory::get('datasource')->getCollection($relation->getForeignCollection());
            $throughTree = new ConditionTreeLeaf($relation->getInverseRelationName(), Operators::EQUAL, $originValue);

            $records = $foreignCollection->list($caller, new PaginatedFilter(conditionTree: $throughTree), new Projection([$relation->getForeignKeyTarget()]));
            $originTree = new ConditionTreeLeaf(
                $relation->getOriginKeyTarget(),
                Operators::IN,
                collect($records)
                    ->map(fn ($record) => $collection->toArray($record)[$relation->getForeignKeyTarget()])
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
        $endPeriod = $operator === Operators::PREVIOUS_X_DAYS
            ? Carbon::now($timezone)->subDays($leaf->getValue())->startOfDay()
            : Carbon::now($timezone)->subDays($leaf->getValue());

        return self::getPreviousConditionTree($leaf->getField(), $startPeriod->toDateTime(), $endPeriod->toDateTime());
    }
}
