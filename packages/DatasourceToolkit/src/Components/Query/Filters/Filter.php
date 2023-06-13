<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class Filter
{
    public function __construct(
        protected ?ConditionTree $conditionTree = null,
        protected ?string        $search = null,
        protected ?bool          $searchExtended = null,
        protected ?string        $segment = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'conditionTree'  => $this->conditionTree,
            'search'         => $this->search,
            'searchExtended' => $this->searchExtended,
            'segment'        => $this->segment,
        ];
    }

    public function isNestable(): bool
    {
        return ! $this->search && ! $this->segment;
    }

    public function override(...$args): self
    {
        return new self(...array_merge($this->toArray(), $args));
    }

    public function nest(string $prefix): self
    {
        if (! $this->isNestable()) {
            throw new ForestException("Filter can't be nested");
        }

        return $this->override(conditionTree: $this->getConditionTree()?->nest($prefix));
    }

    /**
     * @return ConditionTree|null
     */
    public function getConditionTree(): ?ConditionTree
    {
        return $this->conditionTree;
    }

    /**
     * @return string|null
     */
    public function getSearch(): ?string
    {
        return $this->search;
    }

    /**
     * @return bool|null
     */
    public function getSearchExtended(): ?bool
    {
        return $this->searchExtended;
    }

    /**
     * @return string|null
     */
    public function getSegment(): ?string
    {
        return $this->segment;
    }
}
