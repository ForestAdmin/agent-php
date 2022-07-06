<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;

class PaginatedFilter extends Filter
{
    public function __construct(
        $conditionTree = null,
        $search = null,
        $searchExtended = null,
        $segment = null,
        protected ?Sort $sort = null,
        protected ?Page $page = null,
    ) {
        parent::__construct($conditionTree, $search, $searchExtended, $segment);
    }
}
