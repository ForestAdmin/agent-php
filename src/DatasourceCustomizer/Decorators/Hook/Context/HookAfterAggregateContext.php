<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;

class HookAfterAggregateContext extends HookBeforeAggregateContext
{
    public function __construct(
        CollectionContract $collection,
        Caller             $caller,
        Filter             $filter,
        Aggregation        $aggregation,
        int                $limit,
        protected array    $aggregateResult
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
