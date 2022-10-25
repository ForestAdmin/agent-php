<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Collection as IlluminateCollection;

trait CollectionMethods
{
    protected IlluminateCollection $fields;

    protected IlluminateCollection $actions;

    protected bool $searchable = false;

    protected IlluminateCollection $segments;

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
     * @throws ForestException
     */
    public function addAction(string $name, ActionSchema $action): void
    {
        if ($this->actions->has($name)) {
            throw new ForestException('Action ' . $name . ' already defined in collection');
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

    public function addSegment($segment): void
    {
        $this->segments = $this->segments->push($segment);
    }

    public function getSegments(): IlluminateCollection
    {
        return $this->segments;
    }

    public function setSegments(array $segments): Collection
    {
        $this->segments = collect($segments);

        return $this;
    }

    /**
     * @throws ForestException
     */
    public function addField(string $name, ColumnSchema|RelationSchema $field): void
    {
        if ($this->fields->has($name)) {
            throw new ForestException('Field ' . $name . ' already defined in collection');
        }

        $this->fields->put($name, $field);
    }

    public function addFields(array $fields): void
    {
        foreach ($fields as $key => $value) {
            $this->addField($key, $value);
        }
    }
}
