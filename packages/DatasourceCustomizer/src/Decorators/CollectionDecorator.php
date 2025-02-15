<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators;

use ForestAdmin\AgentPHP\DatasourceToolkit\CollectionMethods;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Illuminate\Support\Collection as IlluminateCollection;

class CollectionDecorator implements CollectionContract
{
    use CollectionMethods;

    private ?IlluminateCollection $lastSchema = null;
    private ?CollectionDecorator $parent = null;

    public function __construct(protected CollectionContract|CollectionDecorator $childCollection, protected Datasource $dataSource)
    {
        $this->fields = new IlluminateCollection();
        $this->actions = new IlluminateCollection();
        $this->segments = new IlluminateCollection();
        $this->charts = new IlluminateCollection();

        if ($this->childCollection instanceof self) {
            $this->childCollection->setParent($this);
        }
    }

    public function setParent(CollectionDecorator $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getSchema(): IlluminateCollection
    {
        if (! $this->lastSchema) {
            if (! $this->childCollection instanceof CollectionDecorator) {
                $childSchema = $this->childCollection->getFields();
            } else {
                $childSchema = $this->childCollection->getSchema();
            }
            $this->lastSchema = $this->refineSchema($childSchema);
        }

        return $this->lastSchema;
    }

    public function markSchemaAsDirty(): void
    {
        $this->lastSchema = null;
        $this->parent?->markSchemaAsDirty();
    }

    public function refineSchema(IlluminateCollection $childSchema): IlluminateCollection
    {
        return $childSchema;
    }

    public function isSearchable(): bool
    {
        return $this->childCollection->isSearchable();
    }

    public function getFields(): IlluminateCollection
    {
        return $this->getSchema();
    }

    public function execute(Caller $caller, string $name, array $data, ?Filter $filter = null)
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->execute($caller, $name, $data, $refinedFilter);
    }

    public function getForm(?Caller $caller, string $name, ?array $data = null, ?Filter $filter = null, ?string $changeField = null): array
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->getForm($caller, $name, $data, $refinedFilter, $changeField);
    }

    public function create(Caller $caller, array $data)
    {
        return $this->childCollection->create($caller, $data);
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->list($caller, $refinedFilter, $projection);
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        $this->childCollection->update($caller, $refinedFilter, $patch);
    }

    public function delete(Caller $caller, Filter $filter): void
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        $this->childCollection->delete($caller, $refinedFilter);
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->aggregate($caller, $refinedFilter, $aggregation, $limit);
    }

    protected function refineFilter(?Caller $caller, Filter|PaginatedFilter|null $filter): Filter|PaginatedFilter|null
    {
        return $filter;
    }

    public function getName(): string
    {
        return $this->childCollection->getName();
    }

    public function makeTransformer()
    {
        return $this->childCollection->makeTransformer();
    }

    public function getDataSource(): DatasourceContract
    {
        return $this->dataSource;
    }

    public function getSegments()
    {
        return $this->childCollection->getSegments();
    }

    public function isCountable()
    {
        return $this->childCollection->isCountable();
    }

    public function renderChart(Caller $caller, string $name, array $recordId)
    {
        return $this->childCollection->renderChart($caller, $name, $recordId);
    }

    public function getCharts()
    {
        return $this->childCollection->getCharts();
    }

    public function getActions(): IlluminateCollection
    {
        return $this->childCollection->getActions();
    }

    public function getNativeDriver()
    {
        return $this->childCollection->getNativeDriver();
    }
}
