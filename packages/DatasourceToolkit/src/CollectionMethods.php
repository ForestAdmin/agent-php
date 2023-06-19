<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Support\Collection as IlluminateCollection;

trait CollectionMethods
{
    protected IlluminateCollection $fields;

    protected bool $searchable = false;

    protected IlluminateCollection $segments;

    protected IlluminateCollection $actions;

    protected IlluminateCollection $charts;

    protected bool $countable = true;

    public function getFields(): IlluminateCollection
    {
        return $this->fields;
    }

    public function getActions(): IlluminateCollection
    {
        return $this->actions;
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

    /**
     * @throws ForestException
     */
    public function setField(string $name, ColumnSchema|RelationSchema $field): void
    {
        $this->fields->put($name, $field);
    }

    public function addFields(array $fields): void
    {
        foreach ($fields as $key => $value) {
            $this->addField($key, $value);
        }
    }

    public function isCountable(): bool
    {
        return $this->countable;
    }

    public function setCountable(bool $countable): void
    {
        $this->countable = $countable;
    }

    /**
     * @return IlluminateCollection
     */
    public function getCharts(): IlluminateCollection
    {
        return $this->charts;
    }
}
