<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class DatasourceDecorator extends Datasource
{
    protected DatasourceContract|DatasourceDecorator $childDataSource;

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource, private string $classCollectionDecorator)
    {
        parent::__construct();
        $this->childDataSource = &$childDataSource;
    }

    public function addCollection(CollectionDecorator|CollectionContract $collection): void
    {
        if (! $this->collections->has($collection->getName())) {
            $this->collections->put($collection->getName(), $collection);
        }
    }

    public function build()
    {
        foreach ($this->childDataSource->getCollections() as $collection) {
            $this->addCollection(new $this->classCollectionDecorator($collection, $this));
        }
    }
}
