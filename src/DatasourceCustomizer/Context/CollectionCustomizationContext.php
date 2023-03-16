<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Context;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\RelaxedWrapper\RelaxedCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

class CollectionCustomizationContext extends AgentCustomizationContext
{
    public function __construct(protected CollectionContract $realCollection, Caller $caller)
    {
        parent::__construct($this->realCollection->getDataSource(), $caller);
    }

    public function getCollection(): RelaxedCollection
    {
        return new RelaxedCollection($this->realCollection, $this->caller);
    }
}
