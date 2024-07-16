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
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToManySchema;
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

    public static function makeThroughFilter(CollectionContract $collection, array $id, string $relationName, Caller $caller, Filter $baseForeignFilter): Filter
    {
        /** @var ManyToManySchema $relation */
        $relation = $collection->getFields()[$relationName];
        $originValue = CollectionUtils::getValue($collection, $caller, $id, $relation->getOriginKeyTarget());

        $foreignRelation = CollectionUtils::getThroughTarget($collection, $relationName);
        // Optimization for many to many when there is not search/segment (saves one query)
        if ($foreignRelation && $baseForeignFilter->isNestable()) {
            $baseThroughFilter = $baseForeignFilter->nest($foreignRelation);

            return $baseThroughFilter->override(
                conditionTree: ConditionTreeFactory::intersect(
                    [
                      new ConditionTreeLeaf($relation->getOriginKey(), Operators::EQUAL, $originValue),
                      new ConditionTreeLeaf($relation->getForeignKey(), Operators::PRESENT),
                      $baseThroughFilter->getConditionTree(),
                    ]
                )
            );
        }

        // Otherwise we have no choice but to call the target collection so that search and segment
        // are correctly apply, and then match ids in the though collection.
        $target = $collection->getDataSource()->getCollection($relation->getForeignCollection());
        $records = $target->list(
            $caller,
            self::makeForeignFilter(
                $collection,
                $id,
                $relationName,
                $caller,
                $baseForeignFilter,
            ),
            new Projection($relation->getForeignKeyTarget()),
        );

        return new Filter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    // only children of parent
                    new ConditionTreeLeaf($relation->getOriginKey(), Operators::EQUAL, $originValue),
                    // only the children which match the conditions in baseForeignFilter
                    new ConditionTreeLeaf(
                        $relation->getForeignKey(),
                        Operators::IN,
                        collect($records)->map(fn ($record) => $record[$relation->getForeignKeyTarget()])->toArray()
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
    // todo check this method - it's useful for our agent ?
    public static function makeForeignFilter(CollectionContract $collection, array $id, string $relationName, Caller $caller, Filter $baseForeignFilter): Filter
    {
        $relation = SchemaUtils::getToManyRelation($collection, $relationName);
        $originValue = CollectionUtils::getValue($collection, $caller, $id, $relation->getOriginKeyTarget());

        if ($relation instanceof OneToManySchema || $relation instanceof PolymorphicOneToManySchema) {
            $originTree = new ConditionTreeLeaf($relation->getOriginKey(), Operators::EQUAL, $originValue);
        } else {
            /** @var ManyToManySchema $relation */
            /** @var Collection $foreignCollection */
            $throughCollection = AgentFactory::get('datasource')->getCollection($relation->getThroughCollection());
            $throughTree = ConditionTreeFactory::intersect(
                [
                    new ConditionTreeLeaf($relation->getOriginKey(), Operators::EQUAL, $originValue),
                    new ConditionTreeLeaf($relation->getForeignKey(), Operators::PRESENT),
                ]
            );
            $records = $throughCollection->list($caller, new PaginatedFilter(conditionTree: $throughTree), new Projection([$relation->getForeignKey()]));

            $originTree = new ConditionTreeLeaf(
                $relation->getForeignKeyTarget(),
                Operators::IN,
                collect($records)
                    ->map(fn ($record) => $record[$relation->getForeignKey()])
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
