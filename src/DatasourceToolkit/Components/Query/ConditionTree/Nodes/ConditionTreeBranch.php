<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class ConditionTreeBranch extends ConditionTree
{
    public function __construct(
        protected string  $aggregator,
        protected array   $conditions,
    ) {
    }

    /**
     * @return array
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @return string
     */
    public function getAggregator(): string
    {
        return $this->aggregator;
    }

    public function inverse(): ConditionTree
    {
        // TODO: Implement inverse() method.
    }

    public function replaceLeafs(PlainConditionTree|ConditionTree $handler, $bind): ConditionTree
    {
        // TODO: Implement replaceLeafs() method.
    }

    public function replaceLeafsAsync(PlainConditionTree|ConditionTree $handler, $bind): ConditionTree
    {
        // TODO: Implement replaceLeafsAsync() method.
    }

    public function match(array $record, Collection $collection, string $timezone): bool
    {
        // TODO: Implement match() method.
    }

    public function forEachLeaf(PlainConditionTree|ConditionTree $handler): void
    {
        // TODO: Implement forEachLeaf() method.
    }

    public function everyLeaf(PlainConditionTree|ConditionTree $handler): bool
    {
        // TODO: Implement everyLeaf() method.
    }

    public function someLeaf(PlainConditionTree|ConditionTree $handler): bool
    {
        // TODO: Implement someLeaf() method.
    }

    public function getProjection(): Projection
    {
        // TODO: Implement getProjection() method.
    }
}
