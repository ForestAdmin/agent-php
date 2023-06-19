<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Empty;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class EmptyCollection extends CollectionDecorator
{
    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        if (! $this->returnsEmptySet($filter->getConditionTree())) {
            return parent::list($caller, $filter, $projection);
        }

        return [];
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        if (! $this->returnsEmptySet($filter->getConditionTree())) {
            return parent::update($caller, $filter, $patch);
        }
    }

    public function delete(Caller $caller, Filter $filter): void
    {
        if (! $this->returnsEmptySet($filter->getConditionTree())) {
            parent::delete($caller, $filter);
        }
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        if (! $this->returnsEmptySet($filter->getConditionTree())) {
            return $this->childCollection->aggregate($caller, $filter, $aggregation, $limit);
        }

        return [];
    }

    private function returnsEmptySet(?ConditionTree $tree): bool
    {
        if ($tree instanceof ConditionTreeLeaf) {
            return $this->leafReturnsEmptySet($tree);
        }

        if ($tree instanceof ConditionTreeBranch && $tree->getAggregator() === 'Or') {
            return $this->orReturnsEmptySet($tree->getConditions());
        }

        if ($tree instanceof ConditionTreeBranch && $tree->getAggregator() === 'And') {
            return $this->andReturnsEmptySet($tree->getConditions());
        }

        return false;
    }

    private function leafReturnsEmptySet(ConditionTreeLeaf $leaf): bool
    {
        // Empty 'in` always return zero records.

        return $leaf->getOperator() === Operators::IN && empty($leaf->getValue());
    }

    private function orReturnsEmptySet(array $conditions): bool
    {
        // Or return no records when
        // - they have no conditions
        // - they have only conditions which return zero records.
        return count($conditions) === 0 || collect($conditions)->every(fn ($c) => $this->returnsEmptySet($c));
    }

    private function andReturnsEmptySet(array $conditions): bool
    {
        // There is a leaf which returns zero records
        if (collect($conditions)->some(fn ($c) => $this->returnsEmptySet($c))) {
            return true;
        }
        // Scans for mutually exclusive conditions
        // (this a naive implementation, it will miss many occurences)
        $valuesByField = [];
        $leafs = collect($conditions)->filter(fn ($condition) => $condition instanceof ConditionTreeLeaf);

        /** @var ConditionTreeLeaf $leaf */
        foreach ($leafs->all() as $leaf) {
            if (! isset($valuesByField[$leaf->getField()]) && $leaf->getOperator() === Operators::EQUAL) {
                $valuesByField[$leaf->getField()] = [$leaf->getValue()];
            } elseif (! isset($valuesByField[$leaf->getField()]) && $leaf->getOperator() === Operators::IN) {
                $valuesByField[$leaf->getField()] = $leaf->getValue();
            } elseif (isset($valuesByField[$leaf->getField()]) && $leaf->getOperator() === Operators::EQUAL) {
                // float of $leaf->getValue() because ConditionTreeParser can return an array of float [1.0, 2.0]
                // in php : 1 !== 1.0 :/
                $valuesByField[$leaf->getField()] = in_array((float) $leaf->getValue(), $valuesByField[$leaf->getField()], true) ? [$leaf->getValue()] : [];
            } elseif (isset($valuesByField[$leaf->getField()]) && $leaf->getOperator() === Operators::IN) {
                $valuesByField[$leaf->getField()] = collect($valuesByField[$leaf->getField()])->filter(fn ($v) => in_array($v, $leaf->getValue(), true));
            }
        }

        return collect($valuesByField)->some(fn ($v) => count($v) === 0);
    }
}
