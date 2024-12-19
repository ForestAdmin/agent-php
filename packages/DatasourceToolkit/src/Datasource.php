<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Collection as IlluminateCollection;

class Datasource implements DatasourceContract
{
    protected IlluminateCollection $collections;

    protected IlluminateCollection $charts;

    protected array $liveQueryConnections = [];

    public function __construct()
    {
        $this->charts = new IlluminateCollection();
        $this->collections = new IlluminateCollection();
    }

    public function getCollections(): IlluminateCollection
    {
        return $this->collections;
    }

    public function getCharts(): IlluminateCollection
    {
        return $this->charts;
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

    public function renderChart(Caller $caller, string $name): Chart|array
    {
        throw new ForestException("No chart named '$name' exists on this datasource.");
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function executeNativeQuery(string $connectionName, string $query, array $binds = []): array
    {
        throw new ForestException('this datasource do not support native query.');
    }

    public function setLiveQueryConnections(?array $liveQueryConnections): void
    {
        $this->liveQueryConnections = $liveQueryConnections;
    }

    public function getLiveQueryConnections(): ?array
    {
        return $this->liveQueryConnections;
    }
}
