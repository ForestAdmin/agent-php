<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeAggregateContext;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

class HookAfterAggregateContext extends HookBeforeAggregateContext
{
    public function __construct(
        CollectionContract $collection,
        Caller             $caller,
        Filter             $filter,
        Aggregation        $aggregation,
        protected array    $aggregateResult,
        ?int               $limit = null
    ) {
        parent::__construct($collection, $caller, $filter, $aggregation, $limit);
    }

    /**
     * @return array
     */
    public function getAggregateResult(): array
    {
        return $this->aggregateResult;
    }
}
