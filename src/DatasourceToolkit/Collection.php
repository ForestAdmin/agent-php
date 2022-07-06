<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results\ActionResult;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use Illuminate\Support\Collection as IlluminateCollection;

class Collection implements CollectionContract
{
    protected IlluminateCollection $fields;

    protected IlluminateCollection $actions;

    protected bool $searchable = false;

    protected IlluminateCollection $segments;

    public function __construct(
        protected DatasourceContract $dataSource,
        protected string $name,
    ) {
        $this->fields = new IlluminateCollection();
        $this->actions = new IlluminateCollection();
        $this->segments = new IlluminateCollection();
    }

    public function getDataSource(): DatasourceContract
    {
        return $this->dataSource;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function execute(/*Caller $caller, */string $name, array $formValues, ?Filter $filter = null): ActionResult
    {
        // TODO: Implement execute() method.
        if (! $this->actions->get($name)) {
            throw new \Exception("Action $name is not implemented.");
        }

        // TODO QUESTION HOW TO RETURN ACTIONRESULT + CHECK DUMMYDATA SOURCE PARAMETERS ARE MISSING ? (base.ts -> override async execute(): Promise<ActionResult>)
    }

    public function getForm(/*Caller $caller, */string $name, ?array $formValues = null, ?Filter $filter = null): array
    {
        return [];
    }

    public function create(/*Caller $caller, */array $data): array
    {
        // TODO: Implement create() method.
    }

    public function list(/*Caller $caller, */PaginatedFilter $filter, Projection $projection): array
    {
        // TODO: Implement list() method.
    }

    public function update(/*Caller $caller, */Filter $filter, array $patch): void
    {
        // TODO: Implement update() method.
    }

    public function delete(/*Caller $caller, */Filter $filter): void
    {
        // TODO: Implement delete() method.
    }

    public function aggregate(/*Caller $caller, */Filter $filter, Aggregation $aggregation, ?int $limit): array
    {
        // TODO: Implement aggregate() method.
    }

    public function addFields(array $fields): void
    {
        foreach ($fields as $key => $value) {
            $this->addField($key, $value);
        }
    }

    /**
     * @throws \Exception
     */
    public function addField(string $name, ColumnSchema|RelationSchema $field): void
    {
        if ($this->fields->has($name)) {
            throw new \Exception('Field ' . $name . ' already defined in collection');
        }

        $this->fields->put($name, $field);
    }

    public function getFields(): IlluminateCollection
    {
        return $this->fields;
    }

    public function setFields(array $fields): Collection
    {
        $this->fields = $fields;

        return $this;
    }

    public function addActions(array $actions): void
    {
        foreach ($actions as $key => $value) {
            $this->addAction($key, $value);
        }
    }

    /**
     * @throws \Exception
     */
    public function addAction(string $name, ActionSchema $action): void
    {
        if ($this->actions->has($name)) {
            throw new \Exception('Action ' . $name . ' already defined in collection');
        }

        $this->actions->put($name, $action);
    }

    public function getActions(): IlluminateCollection
    {
        return $this->actions;
    }

    public function setActions(array $actions): Collection
    {
        $this->actions = $actions;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function setSearchable(bool $searchable): Collection
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function addSegments(array $segments): void
    {
        $this->segments = $this->segments->merge($segments);
    }

    public function getSegments(): IlluminateCollection
    {
        return $this->segments;
    }

    public function setSegments(array $segments): Collection
    {
        $this->segments = $segments;

        return $this;
    }
}
