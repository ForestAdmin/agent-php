<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\HookContext;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;

class HookBeforeAggregateContext extends HookContext
{
    public function __construct(
        CollectionContract    $collection,
        Caller                $caller,
        protected Filter      $filter,
        protected Aggregation $aggregation,
        protected int         $limit
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
     * @return Aggregation
     */
    public function getAggregation(): Aggregation
    {
        return $this->aggregation;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }
}
