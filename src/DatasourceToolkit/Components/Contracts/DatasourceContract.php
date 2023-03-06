<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\DataSourceSchema;
use Illuminate\Support\Collection as IlluminateCollection;

interface DatasourceContract
{
    public function getCollections(): IlluminateCollection;

    public function getSchema(): DataSourceSchema;

    public function getCollection(string $name): CollectionContract;

    public function addCollection(CollectionContract $collection): void;

    public function renderChart(Caller $caller, string $name): Chart;
}