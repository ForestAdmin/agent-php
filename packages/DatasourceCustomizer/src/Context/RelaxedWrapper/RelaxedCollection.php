<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Context\RelaxedWrapper;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class RelaxedCollection
{
    public function __construct(protected CollectionContract $collection, protected Caller $caller)
    {
    }

    public function execute(string $name, array $formValues, ?Filter $filter = null)
    {
        return $this->collection->execute($this->caller, $name, $formValues, $filter);
    }

    public function getForm(string $name, ?array $formValues = null, ?Filter $filter = null): array
    {
        return $this->collection->getForm($this->caller, $name, $formValues, $filter);
    }

    public function create(array $data)
    {
        return $this->collection->create($this->caller, $data);
    }

    public function list(PaginatedFilter $filter, Projection $projection): array
    {
        return $this->collection->list($this->caller, $filter, $projection);
    }

    public function update(Filter $filter, array $patch)
    {
        return $this->collection->update($this->caller, $filter, $patch);
    }

    public function delete(Filter $filter): void
    {
        $this->collection->delete($this->caller, $filter);
    }

    public function aggregate(Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        return $this->collection->aggregate($this->caller, $filter, $aggregation, $limit);
    }

    public function getNativeDriver()
    {
        return $this->collection->getNativeDriver();
    }
}
