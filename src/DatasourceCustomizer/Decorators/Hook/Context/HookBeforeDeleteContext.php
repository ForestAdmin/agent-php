<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

class HookBeforeDeleteContext extends HookContext
{
    public function __construct(
        CollectionContract        $collection,
        Caller                    $caller,
        protected Filter $filter,
    ) {
        parent::__construct($collection, $caller);
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return $this->filter;
    }
}
