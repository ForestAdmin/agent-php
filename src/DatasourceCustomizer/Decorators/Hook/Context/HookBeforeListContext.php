<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class HookBeforeListContext extends HookContext
{
    public function __construct(
        CollectionContract        $collection,
        Caller                    $caller,
        protected PaginatedFilter $filter,
        protected Projection      $projection
    ) {
        parent::__construct($collection, $caller);
    }

    /**
     * @return PaginatedFilter
     */
    public function getFilter(): PaginatedFilter
    {
        return $this->filter;
    }

    /**
     * @return Projection
     */
    public function getProjection(): Projection
    {
        return $this->projection;
    }
}
