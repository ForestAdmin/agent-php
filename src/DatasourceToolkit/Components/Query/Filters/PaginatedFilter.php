<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

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

    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'sort' => $this->sort,
                'page' => $this->page,
            ]
        );
    }

    public function override(...$args): Filter
    {
        return new self(...array_merge($this->toArray(), $args));
    }

    public function nest(string $prefix): Filter
    {
        if (! $this->isNestable()) {
            throw new ForestException("Filter can't be nested");
        }

        return $this->override(
            conditionTree: $this->getConditionTree()?->nest($prefix),
            sort: $this->sort?->nest($prefix),
        );
    }
}
