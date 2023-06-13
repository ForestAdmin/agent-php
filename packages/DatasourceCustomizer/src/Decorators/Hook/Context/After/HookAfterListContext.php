<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeListContext;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class HookAfterListContext extends HookBeforeListContext
{
    public function __construct(
        CollectionContract $collection,
        Caller             $caller,
        PaginatedFilter    $filter,
        Projection         $projection,
        protected array    $records,
    ) {
        parent::__construct($collection, $caller, $filter, $projection);
    }

    /**
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }
}
