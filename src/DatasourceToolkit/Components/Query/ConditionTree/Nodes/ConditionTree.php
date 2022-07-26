<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

abstract class ConditionTree
{
    abstract public function inverse(): self;

    abstract public function replaceLeafs(Closure $handler): self;

    abstract public function match(array $record, Collection $collection, string $timezone): bool;

    abstract public function forEachLeaf(Closure $handler): void;

    abstract public function everyLeaf(Closure $handler): bool;

    abstract public function someLeaf(Closure $handler): bool;

    abstract public function getProjection(): Projection;

    public function apply(array $records, Collections $collections, string $timezone): array
    {
        /*apply(records: RecordData[], collection: Collection, timezone: string): RecordData[] {
            return records.filter(record => this.match(record, collection, timezone));
          }*/
    }

    public function nest(string $prefix): self
    {
        return  ! empty($prefix)
            ? $this->replaceLeafs(fn ($leaf) => $leaf->override(['field' => $prefix . ':' . $leaf->getField()]))
            : $this;
    }

    public function unnest(): self
    {
        // todo implement unnest
//        unnest(): ConditionTree {
//                let prefix: string = null;
//            this.someLeaf(leaf => {
//                    [prefix] = leaf.field.split(':');
//
//                    return false;
//                });
//
//            if (!this.everyLeaf(leaf => leaf.field.startsWith(prefix))) {
//                    throw new Error('Cannot unnest condition tree.');
//                }
//
//            return this.replaceLeafs(leaf =>
//              leaf.override({ field: leaf.field.substring(prefix.length + 1) }),
//            );
//          }
    }

    public function replaceFields(Closure $handler): self
    {
        return $this->replaceLeafs(fn (ConditionTreeLeaf $leaf) => $leaf->override(['field' => $handler($leaf->getField())]));
    }
}
