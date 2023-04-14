<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Segment;

use Closure;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
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

        if ($segment && isset($this->computedSegments[$segment])) {
            $definition = $this->computedSegments[$segment];
            $result = $definition(new CollectionCustomizationContext($this, $caller));

            if ($result instanceof ConditionTreeLeaf) {
                $result = ['field' => $result->getField(), 'operator' => $result->getOperator(), 'value' => $result->getValue()];
            }

            $conditionTreeSegment = ConditionTreeFactory::fromArray($result);
            ConditionTreeValidator::validate($conditionTreeSegment, $this);
            $conditionTree = ConditionTreeFactory::intersect([$filter->getConditionTree(), $conditionTreeSegment]);

            return $filter->override(conditionTree: $conditionTree, segment: null);
        }

        return $filter;
    }
}
