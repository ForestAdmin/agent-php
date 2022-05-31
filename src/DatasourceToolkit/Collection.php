<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
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
        $this->segments->merge($segments);
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
