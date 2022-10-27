<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Search\SearchCollection;

class DecoratorsStack
{
    public DatasourceContract|DatasourceDecorator $dataSource;
    public DatasourceDecorator $earlyComputed;
    public DatasourceDecorator $lateComputed;
    public DatasourceDecorator $search;

    public function __construct(DatasourceContract $dataSource)
    {
        $last = &$dataSource;

        /* eslint-disable no-multi-assign */
        // Step 0: Do not query datasource when we know the result with yield an empty set.
        // last = this.empty = new DataSourceDecorator(last, EmptyCollectionDecorator);

        // Step 1: Computed-Relation-Computed sandwich (needed because some emulated relations depend
        // on computed fields, and some computed fields depend on relation...)
        // Note that replacement goes before emulation, as replacements may use emulated operators.
//        $earlyComputed = new DataSourceDecorator($last, ComputedCollection::class);
//        $last = $this->earlyComputed = &$earlyComputed;
        $lateComputed = new DataSourceDecorator($last, ComputedCollection::class);
        $last = $this->lateComputed = &$lateComputed;

        // Step 2: Those need access to all fields. They can be loaded in any order.
        $last = $this->search = new DataSourceDecorator($last, SearchCollection::class);
//        last = this.segment = new DataSourceDecorator(last, SegmentCollectionDecorator);
//        last = this.sortEmulate = new DataSourceDecorator(last, SortEmulateCollectionDecorator);
//        last = this.write = new DataSourceDecorator(last, WriteCollectionDecorator);


        $this->dataSource = &$last;
    }

    public function build(): void
    {
        $this->lateComputed->build();
        $this->search->build();
        $this->dataSource->build();
    }
}
