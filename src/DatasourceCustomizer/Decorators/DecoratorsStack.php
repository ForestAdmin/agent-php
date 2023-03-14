<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Empty\EmptyCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\OperatorsEmulate\OperatorsEmulateCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\OperatorsReplace\OperatorsReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Relation\RelationCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\SchemaCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Search\SearchCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Segment\SegmentCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Sort\SortCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Validation\ValidationCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

class DecoratorsStack
{
    public DatasourceContract|DatasourceDecorator $dataSource;
    public DatasourceDecorator $empty;
    public DatasourceDecorator $earlyComputed;
//    public DatasourceDecorator $earlyOpEmulate;
//    public DatasourceDecorator $earlyOpReplace;
    public DatasourceDecorator $lateComputed;
//    public DatasourceDecorator $lateOpEmulate;
//    public DatasourceDecorator $lateOpReplace;
    public DatasourceDecorator $search;
    public DatasourceDecorator $segment;
    public DatasourceDecorator $sort;
    public DatasourceDecorator $relation;
    public DatasourceDecorator $action;
    public DatasourceDecorator $schema;

    public function __construct(DatasourceContract $dataSource)
    {
        $last = &$dataSource;

        $last = $this->empty = new DatasourceDecorator($last, EmptyCollection::class);

        // Step 0: Do not query datasource when we know the result with yield an empty set.
        // last = this.empty = new DataSourceDecorator(last, EmptyCollectionDecorator);

        // Step 1: Computed-Relation-Computed sandwich (needed because some emulated relations depend
        // on computed fields, and some computed fields depend on relation...)
        // Note that replacement goes before emulation, as replacements may use emulated operators.
        $last = $this->earlyComputed = new DatasourceDecorator($last, ComputedCollection::class);
//        $last = $this->earlyOpEmulate = new DatasourceDecorator($last, OperatorsEmulateCollection::class);
//        $last = $this->earlyOpReplace = new DatasourceDecorator($last, OperatorsReplaceCollection::class);
        $last = $this->relation = new DatasourceDecorator($last, RelationCollection::class);
        $last = $this->lateComputed = new DatasourceDecorator($last, ComputedCollection::class);
//        $last = $this->lateOpEmulate = new DatasourceDecorator($last, OperatorsEmulateCollection::class);
//        $last = $this->lateOpReplace = new DatasourceDecorator($last, OperatorsReplaceCollection::class);

        // Step 2: Those need access to all fields. They can be loaded in any order.
        $last = $this->search = new DatasourceDecorator($last, SearchCollection::class);
        $last = $this->segment = new DatasourceDecorator($last, SegmentCollection::class);
        $last = $this->sort = new DatasourceDecorator($last, SortCollection::class);

        // Step 3: Access to all fields AND emulated capabilities
        $last = $this->action = new DatasourceDecorator($last, ActionCollection::class);
        $last = $this->schema = new DataSourceDecorator($last, SchemaCollection::class);

        $this->dataSource = &$last;
    }

    public function build(): void
    {
        $this->empty->build();
        $this->earlyComputed->build();
//        $this->earlyOpEmulate->build();
//        $this->earlyOpReplace->build();
        $this->relation->build();
        $this->lateComputed->build();
//        $this->lateOpEmulate->build();
//        $this->lateOpReplace->build();
        $this->search->build();
        $this->segment->build();
        $this->sort->build();
        $this->action->build();
        $this->schema->build();
        $this->dataSource->build();
    }
}
