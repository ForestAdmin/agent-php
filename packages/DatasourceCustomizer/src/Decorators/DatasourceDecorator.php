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

    protected IlluminateCollection $decorators;

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource, private string $classCollectionDecorator)
    {
        parent::__construct();
        $this->decorators = new IlluminateCollection();
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

    public function getCollections(): IlluminateCollection
    {
        return $this->childDataSource->getCollections()->map(fn ($collection) => $this->getCollection($collection->getName()));
    }

    public function getCollection(string $name): CollectionContract
    {
        $collection = $this->childDataSource->getCollection($name);

        if (! $this->decorators->has($collection->getName())) {
            $this->decorators->put($collection->getName(), new $this->classCollectionDecorator($collection, $this));
        }

        return $this->decorators->get($collection->getName());
    }

    public function renderChart(Caller $caller, string $name): Chart|array
    {
        return $this->childDataSource->renderChart($caller, $name);
    }

    public function getChildDataSource(): DatasourceContract|DatasourceDecorator
    {
        return $this->childDataSource;
    }
}
