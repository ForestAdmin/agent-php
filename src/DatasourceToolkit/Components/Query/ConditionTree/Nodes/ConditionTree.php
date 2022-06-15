<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

abstract class ConditionTree
{
    abstract public function inverse(): self;

    /**
     * @param ConditionTree|PlainConditionTree $handler
     * @param mixed                            $bind
     * @return mixed
     */
    abstract public function replaceLeafs(ConditionTree|PlainConditionTree $handler, $bind): self;

    abstract public function replaceLeafsAsync(ConditionTree|PlainConditionTree $handler, $bind): self;

    abstract public function match(array $record, Collection $collection, string $timezone): bool;

    abstract public function forEachLeaf(ConditionTree|PlainConditionTree $handler): void;

    abstract public function everyLeaf(ConditionTree|PlainConditionTree $handler): bool;

    abstract public function someLeaf(ConditionTree|PlainConditionTree $handler): bool;

    abstract public function getProjection(): Projection;

    public function apply(array $records, Collections $collections, string $timezone): array
    {
        // todo implement apply
        /*apply(records: RecordData[], collection: Collection, timezone: string): RecordData[] {
            return records.filter(record => this.match(record, collection, timezone));
          }*/
    }

    public function nest(string $prefix): self
    {
        //return ! empty($prefix) ?
        // todo implement nest
//        nest(prefix: string): ConditionTree {
//        return prefix && prefix.length
//            ? this.replaceLeafs(leaf => leaf.override({ field: `${prefix}:${leaf.field}` }))
//      : this;
//  }
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

    public function replaceFields(string $handler): self
    {
        // todo implement replaceFields
//        replaceFields(handler: (field: string) => string): ConditionTree {
//                return this.replaceLeafs(leaf => leaf.override({ field: handler(leaf.field) }));
//          }
    }

    /*
     * abstract inverse(): ConditionTree;
  abstract replaceLeafs(handler: LeafReplacer, bind?: unknown): ConditionTree;
  abstract replaceLeafsAsync(handler: AsyncLeafReplacer, bind?: unknown): Promise<ConditionTree>;

  abstract match(record: RecordData, collection: Collection, timezone: string): boolean;

  abstract forEachLeaf(handler: LeafCallback): void;
  abstract everyLeaf(handler: LeafTester): boolean;
  abstract someLeaf(handler: LeafTester): boolean;

  abstract get projection(): Projection;

  apply(records: RecordData[], collection: Collection, timezone: string): RecordData[] {
    return records.filter(record => this.match(record, collection, timezone));
  }

  nest(prefix: string): ConditionTree {
    return prefix && prefix.length
      ? this.replaceLeafs(leaf => leaf.override({ field: `${prefix}:${leaf.field}` }))
      : this;
  }

  unnest(): ConditionTree {
    let prefix: string = null;
    this.someLeaf(leaf => {
      [prefix] = leaf.field.split(':');

      return false;
    });

    if (!this.everyLeaf(leaf => leaf.field.startsWith(prefix))) {
      throw new Error('Cannot unnest condition tree.');
    }

    return this.replaceLeafs(leaf =>
      leaf.override({ field: leaf.field.substring(prefix.length + 1) }),
    );
  }

  replaceFields(handler: (field: string) => string): ConditionTree {
    return this.replaceLeafs(leaf => leaf.override({ field: handler(leaf.field) }));
  }
     */
}
