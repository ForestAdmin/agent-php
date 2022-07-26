<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
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

    public function replaceLeafs(Closure $handler): ConditionTree
    {
        return new ConditionTreeBranch(
            $this->aggregator,
            array_map(static fn ($condition) => $condition->replaceLeafs($handler), $this->getConditions()),
        );
    }

    public function match(array $record, Collection $collection, string $timezone): bool
    {
        // TODO: Implement match() method.
    }

    public function forEachLeaf(Closure $handler): void
    {
        // TODO: Implement forEachLeaf() method.
    }

    public function everyLeaf(Closure $handler): bool
    {
        return collect($this->getConditions())->every(fn (ConditionTreeLeaf $condition) => $condition->everyLeaf($handler));
    }

    public function someLeaf(Closure $handler): bool
    {
        return collect($this->getConditions())->contains(fn (ConditionTreeLeaf $condition) => $condition->someLeaf($handler));
    }

    public function getProjection(): Projection
    {
        // TODO: Implement getProjection() method.
    }
}
