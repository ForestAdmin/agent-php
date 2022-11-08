<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Segment;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Validations\ConditionTreeValidator;
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

    public function addSegment(string $name, \Closure $definition)
    {
        $this->computedSegments[$name] = $definition;
        $this->markSchemaAsDirty();
    }

    public function refineFilter(Caller $caller, ?Filter $filter): ?Filter
    {
        if (! $filter) {
            return null;
        }

        $segment = $filter->getSegment();

        if ($segment && $this->computedSegments[$segment]) {
            $definition = $this->computedSegments[$segment];
            $result = call_user_func($definition);

            $conditionTreeSegment = ConditionTreeFactory::fromArray($result);
            ConditionTreeValidator::validate($conditionTreeSegment, $this);
            $conditionTree = ConditionTreeFactory::intersect([$filter->getConditionTree(), $conditionTreeSegment]);

            return $filter->override(conditionTree: $conditionTree, segment: null);
        }

        return $filter;
    }
}
