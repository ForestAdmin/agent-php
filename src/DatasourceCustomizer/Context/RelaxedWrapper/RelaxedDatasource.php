<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Context\RelaxedWrapper;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

class RelaxedDatasource
{
    public function __construct(protected DatasourceContract $datasource, protected Caller $caller)
    {
    }

    public function getCollection(string $name): RelaxedCollection
    {
        return new RelaxedCollection($this->datasource->getCollection($name), $this->caller);
    }
}
