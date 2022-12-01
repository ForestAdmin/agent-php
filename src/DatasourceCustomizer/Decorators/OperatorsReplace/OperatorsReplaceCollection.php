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
use Illuminate\Support\Collection as IlluminateCollection;

class OperatorsReplaceCollection extends CollectionDecorator
{
    public function getFields(): IlluminateCollection
    {
        $fields = $this->childCollection->getFields();

        /**
         * @var ColumnSchema $schema
         */
        foreach ($fields as $schema) {
            if ($schema instanceof ColumnSchema) {
                $newOperators = collect(Operators::getAllOperators())
                    ->filter(fn ($operator) => ConditionTreeEquivalent::hasEquivalentTree($operator, $schema->getFilterOperators(), $schema->getColumnType()));

                $schema->setFilterOperators([
                    ...$schema->getFilterOperators(),
                    ...$newOperators,
                ]);
            }
        }

        return $fields;
    }

    protected function refineFilter(Caller $caller, Filter|PaginatedFilter|null $filter): Filter|PaginatedFilter|null
    {
        return $filter?->override(
            conditionTree: $filter->getConditionTree()?->replaceLeafs(function ($leaf) use ($caller) {
                $schema = CollectionUtils::getFieldSchema($this->childCollection, $leaf->getField());

                return ConditionTreeEquivalent::getEquivalentTree($leaf, $schema->getFilterOperators(), $schema->getColumnType(), $caller->getTimezone());
            })
        );
    }
}
