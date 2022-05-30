<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class Collection implements CollectionContract
{
    /** @var ColumnSchema[]|RelationSchema[] */
    protected array $fields = [];

    /** @var ActionSchema[] */
    protected array $actions = [];

    protected bool $searchable = false;

    protected array $segments = [];

    public function __construct(
        protected DatasourceContract $dataSource,
        protected string $name,
    ) {
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
        if (isset($this->fields[$name])) {
            throw new \Exception('Field ' . $name . ' already defined in collection');
        }

        $this->fields[$name] = $field;
    }

    public function getFields(): array
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
        if (isset($this->actions[$name])) {
            throw new \Exception('Action ' . $name . ' already defined in collection');
        }

        $this->actions[$name] = $action;
    }

    public function getActions(): array
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

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function setSegments(array $segments): Collection
    {
        $this->segments = $segments;
        return $this;
    }
}
