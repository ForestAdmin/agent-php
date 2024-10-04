<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Binary\BinaryCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart\ChartDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Empty\EmptyCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\HookCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\OperatorsEmulate\OperatorsEmulateCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\OperatorsReplace\OperatorsReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection\PublicationCollectionDatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Relation\RelationCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameField\RenameFieldCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\SchemaCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Search\SearchCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Segment\SegmentCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Sort\SortCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Validation\ValidationCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use Illuminate\Support\Collection;

class DecoratorsStack
{
    public DatasourceContract|DatasourceDecorator $dataSource;
    public DatasourceDecorator $empty;
    public DatasourceDecorator $earlyComputed;
    public DatasourceDecorator $earlyOpEmulate;
    public DatasourceDecorator $earlyOpReplace;
    public DatasourceDecorator $lateComputed;
    public DatasourceDecorator $lateOpEmulate;
    public DatasourceDecorator $lateOpReplace;
    public DatasourceDecorator $search;
    public DatasourceDecorator $segment;
    public DatasourceDecorator $sort;
    public DatasourceDecorator $relation;
    public ChartDataSourceDecorator $chart;
    public DatasourceDecorator $action;
    public DatasourceDecorator $schema;
    public DatasourceDecorator $write;
    public DatasourceDecorator $hook;
    public DatasourceContract $validation;
    public DatasourceDecorator $binary;
    public DatasourceDecorator $publication;
    public DatasourceDecorator $renameField;

    private Collection $customizations;

    public function __construct(DatasourceContract $dataSource)
    {
        $this->customizations = collect();

        $last = &$dataSource;

        // Step 0: Do not query datasource when we know the result with yield an empty set.
        $last = $this->empty = new DatasourceDecorator($last, EmptyCollection::class);

        // Step 1: Computed-Relation-Computed sandwich (needed because some emulated relations depend
        // on computed fields, and some computed fields depend on relation...)
        // Note that replacement goes before emulation, as replacements may use emulated operators.
        $last = $this->earlyComputed = new DatasourceDecorator($last, ComputedCollection::class);
        $last = $this->earlyOpEmulate = new DatasourceDecorator($last, OperatorsEmulateCollection::class);
        $last = $this->earlyOpReplace = new DatasourceDecorator($last, OperatorsReplaceCollection::class);
        $last = $this->relation = new DatasourceDecorator($last, RelationCollection::class);
        $last = $this->lateComputed = new DatasourceDecorator($last, ComputedCollection::class);
        $last = $this->lateOpEmulate = new DatasourceDecorator($last, OperatorsEmulateCollection::class);
        $last = $this->lateOpReplace = new DatasourceDecorator($last, OperatorsReplaceCollection::class);

        // Step 2: Those need access to all fields. They can be loaded in any order.
        $last = $this->search = new DatasourceDecorator($last, SearchCollection::class);
        $last = $this->segment = new DatasourceDecorator($last, SegmentCollection::class);
        $last = $this->sort = new DatasourceDecorator($last, SortCollection::class);

        // Step 3: Access to all fields AND emulated capabilities
        $last = $this->chart = new ChartDataSourceDecorator($last);
        $last = $this->action = new DatasourceDecorator($last, ActionCollection::class);
        $last = $this->schema = new DataSourceDecorator($last, SchemaCollection::class);
        $last = $this->write = new WriteDataSourceDecorator($last);
        $last = $this->hook = new DatasourceDecorator($last, HookCollection::class);
        $last = $this->validation = new DatasourceDecorator($last, ValidationCollection::class);
        $last = $this->binary = new DatasourceDecorator($last, BinaryCollection::class);

        // Step 4: Renaming must be either the very first or very last so that naming in customer code is consistent.
        $last = $this->publication = new PublicationCollectionDatasourceDecorator($last);
        $last = $this->renameField = new DatasourceDecorator($last, RenameFieldCollection::class);

        $this->dataSource = &$last;
    }

    public function queueCustomization(\Closure $customization): void
    {
        $this->customizations->push($customization);
    }

    public function applyQueuedCustomizations(): void
    {
        $queuedCustomizations = $this->customizations->slice(0);
        $this->customizations = collect();

        while ($queuedCustomizations->isNotEmpty()) {
            call_user_func($queuedCustomizations->shift());
            $this->applyQueuedCustomizations();
        }
    }
}
