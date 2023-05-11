<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

class HookBeforeUpdateContext extends HookContext
{
    public function __construct(
        CollectionContract $collection,
        Caller             $caller,
        protected Filter   $filter,
        protected array    $patch
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

    /**
     * @return array
     */
    public function getPatch(): array
    {
        return $this->patch;
    }
}
