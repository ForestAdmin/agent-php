<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Segment;

use Closure;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ConditionTreeValidator;
use Illuminate\Support\Collection as IlluminateCollection;

class SegmentCollection extends CollectionDecorator
{
    private array $computedSegments = [];

    public function getSegments(): IlluminateCollection
    {
        $segments = parent::getSegments();
        $merged = $segments->merge(array_keys($this->computedSegments));

        return $merged;
    }

    public function addSegment(string $name, Closure $definition)
    {
        $this->computedSegments[$name] = $definition;
        $this->markSchemaAsDirty();
    }

    public function refineFilter(Caller $caller, PaginatedFilter|Filter|null $filter): PaginatedFilter|Filter|null
    {
        if (! $filter) {
            return null;
        }

        $segment = $filter->getSegment();

        if ($segment && $this->computedSegments[$segment]) {
            $definition = $this->computedSegments[$segment];
            $result = $definition();

            $conditionTreeSegment = ConditionTreeFactory::fromArray($result);
            ConditionTreeValidator::validate($conditionTreeSegment, $this);
            $conditionTree = ConditionTreeFactory::intersect([$filter->getConditionTree(), $conditionTreeSegment]);

            return $filter->override(conditionTree: $conditionTree, segment: null);
        }

        return $filter;
    }
}
