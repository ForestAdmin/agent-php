<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeCreateContext;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

class HookAfterCreateContext extends HookBeforeCreateContext
{
    public function __construct(
        CollectionContract $collection,
        Caller             $caller,
        array              $data,
        protected array    $record
    ) {
        parent::__construct($collection, $caller, $data);
    }

    /**
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }
}
