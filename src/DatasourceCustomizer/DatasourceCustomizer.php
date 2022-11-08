<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DecoratorsStack;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\DataSourceSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Illuminate\Support\Collection as IlluminateCollection;

class DatasourceCustomizer implements DatasourceContract
{
    protected Datasource $compositeDatasource;

    protected DecoratorsStack $stack;

    public function __construct()
    {
        $this->compositeDatasource = new Datasource();
        $this->stack = new DecoratorsStack($this->compositeDatasource);
    }

    public function addDatasource(DatasourceContract $datasource, array $options = [])
    {
        if (isset($options['include']) || isset($options['exclude'])) {

        }
    }


    public function getCollections(): IlluminateCollection
    {
        // TODO: Implement getCollections() method.
    }

    public function getSchema(): DataSourceSchema
    {
        // TODO: Implement getSchema() method.
    }

    public function getCollection(string $name): CollectionContract
    {
        // TODO: Implement getCollection() method.
    }

    public function addCollection(CollectionContract $collection): void
    {
        // TODO: Implement addCollection() method.
    }

    public function renderChart(Caller $caller, string $name): Chart
    {
        // TODO: Implement renderChart() method.
    }

}
