<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceToolkit\Factories;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\PlainConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class ConditionTreeUnknown extends ConditionTree
{
    public function inverse(): ConditionTree
    {
        // TODO: Implement inverse() method.
    }

    public function replaceLeafs(ConditionTree|PlainConditionTree $handler, $bind): ConditionTree
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
