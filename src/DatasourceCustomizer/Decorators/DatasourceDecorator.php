<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Illuminate\Support\Collection as IlluminateCollection;

class DatasourceDecorator extends Datasource
{
    protected DatasourceContract|DatasourceDecorator $childDataSource;

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource, private string $classCollectionDecorator)
    {
        parent::__construct();
        $this->childDataSource = &$childDataSource;
    }

    public function getCharts(): IlluminateCollection
    {
        return $this->childDataSource->getCharts();
    }

    public function addCollection(CollectionDecorator|CollectionContract $collection): void
    {
        if (! $this->collections->has($collection->getName())) {
            $this->collections->put($collection->getName(), $collection);
        }
    }

    public function renderChart(Caller $caller, string $name): Chart
    {
        return $this->childDataSource->renderChart($caller, $name);
    }

    public function build()
    {
        foreach ($this->childDataSource->getCollections() as $collection) {
            $this->addCollection(new $this->classCollectionDecorator($collection, $this));
        }
    }
}
