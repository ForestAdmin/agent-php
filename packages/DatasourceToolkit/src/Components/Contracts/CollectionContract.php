<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

interface CollectionContract
{
    public function getDataSource(): DatasourceContract;

    public function getName(): string;

    public function execute(Caller $caller, string $name, array $formValues, ?Filter $filter = null);

    public function getForm(Caller $caller, string $name, ?array $formValues = null, ?Filter $filter = null): array;

    public function create(Caller $caller, array $data);

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array;

    public function update(Caller $caller, Filter $filter, array $patch);

    public function delete(Caller $caller, Filter $filter): void;

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null);

    public function renderChart(Caller $caller, string $name, array $recordId);
}
