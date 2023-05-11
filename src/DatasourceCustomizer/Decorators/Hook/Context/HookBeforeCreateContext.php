<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

class HookBeforeCreateContext extends HookContext
{
    public function __construct(
        CollectionContract $collection,
        Caller             $caller,
        protected array    $data,
    ) {
        parent::__construct($collection, $caller);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
