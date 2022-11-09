<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results\ActionResult;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;

interface CollectionContract
{
    public function getDataSource(): DatasourceContract;

    public function getName(): string;

    public function getClassName(): string;

    public function execute(Caller $caller, string $name, array $formValues, ?Filter $filter = null): ActionResult;

    public function getForm(Caller $caller, string $name, ?array $formValues = null, ?Filter $filter = null): array;

    public function toArray($record): array;

    public function create(Caller $caller, array $data);

    public function show(Caller $caller, Filter $filter, $id, Projection $projection);

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array;

    public function export(Caller $caller, Filter $filter, Projection $projection): array;

    public function update(Caller $caller, Filter $filter, $id, array $patch);

    public function delete(Caller $caller, Filter $filter, $id): void;

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null);

    public function associate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void;

    public function dissociate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void;
}
