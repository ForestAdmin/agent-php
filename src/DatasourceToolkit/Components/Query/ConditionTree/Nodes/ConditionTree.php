<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Str;

abstract class ConditionTree
{
    abstract public function inverse(): self;

    abstract public function replaceLeafs(Closure $handler): ?self;

    abstract public function match(array $record, Collection $collection, string $timezone): bool;

    abstract public function forEachLeaf(Closure $handler): self;

    abstract public function everyLeaf(Closure $handler): bool;

    abstract public function someLeaf(Closure $handler): bool;

    abstract public function getProjection(): Projection;

    public function apply(array $records, CollectionContract $collection, string $timezone): array
    {
        return collect($records)->filter(fn ($record) => $this->match($record, $collection, $timezone))->toArray();
    }

    public function nest(string $prefix): self
    {
        return ! empty($prefix)
            ? $this->replaceLeafs(fn ($leaf) => $leaf->override(field: $prefix . ':' . $leaf->getField()))
            : $this;
    }

    public function unnest(): self
    {
        if ($this instanceof ConditionTreeBranch) {
            $field = $this->getConditions()[0]->getField();
        } else {
            $field = $this->getField();
        }
        [$prefix] = explode(':', $field);

        if (! $this->everyLeaf(
            fn (ConditionTreeLeaf $leaf) => Str::startsWith($leaf->getField(), "$prefix:")
        )) {
            throw new ForestException('Cannot unnest condition tree.');
        }

        return $this->replaceLeafs(
            fn (ConditionTreeLeaf $leaf) => $leaf->override(field: Str::substr($leaf->getField(), Str::length($prefix) + 1))
        );
    }

    public function replaceFields(Closure $handler): self
    {
        return $this->replaceLeafs(fn (ConditionTreeLeaf $leaf) => $leaf->override(field: $handler($leaf->getField())));
    }
}
