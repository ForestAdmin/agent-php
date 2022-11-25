<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\OperatorsReplace;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeEquivalent;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class OperatorsReplaceCollection extends CollectionDecorator
{
    protected function refineSchema($childSchema)
    {
        $fields = [];

        /**
         * @var ColumnSchema $schema
         * @var string $name
         */
        foreach ($childSchema->getFields() as $schema => $name) {
            if ($schema->getType() === 'Column') {
                $newOperators = collect(Operators::getAllOperators())
                    ->filter(fn ($operator) => ConditionTreeEquivalent::hasEquivalentTree($operator, $schema->getFilterOperators(), $schema->getColumnType()));

                $fields[$name] = [...$schema, 'filterOperators' => $newOperators];
            } else {
                $fields[$name] = $schema;
            }
        }

        return [...$childSchema, $fields];
    }

    protected function refineFilter(Caller $caller, Filter|PaginatedFilter|null $filter): Filter|PaginatedFilter|null
    {
        return $filter?->override(
            conditionTree: $filter->getConditionTree()?->replaceLeafs(function ($leaf) {
                $schema = CollectionUtils::getFieldSchema($this->childCollection, $leaf->getField());

                return ConditionTreeEquivalent::getEquivalentTree($leaf, $schema->getFilterOperators(), $schema->getColumnType(), $caller->getTimezone());
            })
        );
    }
}
