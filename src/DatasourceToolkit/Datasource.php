<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\DataSourceSchema;
use Illuminate\Support\Collection as IlluminateCollection;

class Datasource implements DatasourceContract
{
    protected IlluminateCollection $collections;

    protected DataSourceSchema $schema;

    public function __construct()
    {
        $this->schema = new DataSourceSchema();
        $this->collections = new IlluminateCollection();
    }

    public function getCollections(): IlluminateCollection
    {
        return $this->collections;
    }

    public function getSchema(): DataSourceSchema
    {
        return $this->schema;
    }

    public function getCollection(string $name): CollectionContract
    {
        $collection = $this->collections->first(fn ($item) => $item->getName() === $name);

        return $collection ?? throw new ForestException("Collection $name not found.");
    }

    /**
     * @throws ForestException
     */
    public function addCollection(CollectionContract $collection): void
    {
        if ($this->collections->has($collection->getName())) {
            throw new ForestException('Collection ' . $collection->getName() . ' already defined in datasource');
        }
        $this->collections->put($collection->getName(), $collection);
    }

    public function renderChart(Caller $caller, string $name): Chart
    {
    }
}
