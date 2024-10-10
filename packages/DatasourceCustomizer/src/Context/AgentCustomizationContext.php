<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Context;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\RelaxedWrapper\RelaxedDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

class AgentCustomizationContext
{
    public function __construct(protected DatasourceContract $realDatasource, protected ?Caller $caller)
    {
    }

    public function getCaller(): Caller
    {
        return $this->caller;
    }

    public function getDatasource(): RelaxedDatasource
    {
        return new RelaxedDatasource($this->realDatasource, $this->caller);
    }
}
