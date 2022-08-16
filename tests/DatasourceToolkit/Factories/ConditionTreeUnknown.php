<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceToolkit\Factories;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class ConditionTreeUnknown extends ConditionTree
{

    public function inverse(): ConditionTree
    {
        // TODO: Implement inverse() method.
    }

    public function replaceLeafs(Closure $handler): ConditionTree
    {
        // TODO: Implement replaceLeafs() method.
    }

    public function match(array $record, Collection $collection, string $timezone): bool
    {
        // TODO: Implement match() method.
    }

    public function forEachLeaf(Closure $handler): ConditionTree
    {
        // TODO: Implement forEachLeaf() method.
    }

    public function everyLeaf(Closure $handler): bool
    {
        // TODO: Implement everyLeaf() method.
    }

    public function someLeaf(Closure $handler): bool
    {
        // TODO: Implement someLeaf() method.
    }

    public function getProjection(): Projection
    {
        // TODO: Implement getProjection() method.
    }
}
