<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results\ActionResult;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;

interface CollectionContract
{
    public function getDataSource(): DatasourceContract;

    public function getName(): string;

    public function getClassName(): string;

    public function hydrate(array $args): void;

    public function execute(/*Caller $caller, */string $name, array $formValues, ?Filter $filter = null): ActionResult;

    public function getForm(/*Caller $caller, */string $name, ?array $formValues = null, ?Filter $filter = null): array;

    public function create(Caller $caller, array $data);

    public function show(Caller $caller, PaginatedFilter $filter, $id, Projection $projection);

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection, bool $arrayObject = true): array;

    public function update(Caller $caller, Filter $filter, $id, array $patch);

    public function delete(Caller $caller, Filter $filter, $id): void;

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null);

    public function associate(Caller $caller, $id, OneToManySchema|ManyToManySchema $relation, $childId): void;

    public function dissociate(Caller $caller, $id, OneToManySchema|ManyToManySchema $relation, $childId): void;
}
