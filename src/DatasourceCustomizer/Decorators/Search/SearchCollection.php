<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Search;

use Closure;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\Relations\OneToOneSchema;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class SearchCollection extends CollectionDecorator
{
    private ?Closure $replacer = null;

    /**
     * @throws \ReflectionException
     */
    public function replaceSearch(Closure $replacer): void
    {
        // todo check closure arguments type with ReflectionFunction
        $this->replacer = $replacer;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    protected function refineSchema($childSchema /*: CollectionSchema*/) /* CollectionSchema*/
    {
        // todo
    }

    public function refineFilter(Caller $caller, ?Filter $filter): Filter
    {
        if ($filter->getSearch() === null || trim($filter->getSearch()) === '') {
            return $filter->override(search: null);
        }

        if ($this->replacer || ! $this->childCollection->isSearchable()) {
            $tree = $this->defaultReplacer($filter->getSearch(), $filter->getSearchExtended());

            if ($this->replacer) {
                $plainTree = call_user_func($this->replacer, $filter->getSearch(), $filter->getSearchExtended());
                $tree = ConditionTreeFactory::fromArray($plainTree);
            }

            return $filter->override(
                conditionTree: ConditionTreeFactory::intersect([$filter->getConditionTree(), $tree]),
                search: null
            );
        }

        return $filter;
    }

    private function defaultReplacer(string $search, bool $searchExtended): ConditionTree
    {
        $searchableFields = $this->getSearchableFields($this->childCollection, $searchExtended);
        $conditions = $searchableFields->map(
            fn ($schema, $field) => $this->buildCondition($field, $schema, $search)
        )
            ->filter()
            ->toArray();

        return ConditionTreeFactory::union($conditions);
    }

    private function getSearchableFields(CollectionDecorator $collection, bool $searchExtended): IlluminateCollection
    {
        $fields = collect();
        foreach ($collection->getFields() as $name => $field) {
            if ($field instanceof ColumnSchema) {
                $fields->put($name, $field);
            }

            if ($searchExtended && ($field instanceof ManyToOneSchema || $field instanceof OneToOneSchema)) {
                $related = $collection->getDataSource()->getCollection($field->getForeignCollection());

                foreach ($related->getFields() as $subName => $subField) {
                    if ($subField instanceof ColumnSchema) {
                        $fields->put("$name:$subName", $subField);
                    }
                }
            }
        }

        return $fields;
    }

    private function buildCondition(string $field, ColumnSchema $schema, string $search): ?ConditionTree
    {
        if (is_numeric($search)
            && $schema->getColumnType() === PrimitiveType::NUMBER
            && in_array(Operators::EQUAL, $schema->getFilterOperators(), true)
        ) {
            return new ConditionTreeLeaf($field, Operators::EQUAL, $search);
        }

        if ($schema->getColumnType() === PrimitiveType::ENUM && in_array(Operators::EQUAL, $schema->getFilterOperators(), true)) {
            $searchValue = $this->lenientFind($schema->getEnumValues(), $search);

            if ($searchValue) {
                return new ConditionTreeLeaf($field, Operators::EQUAL, $searchValue);
            }
        }

        if ($schema->getColumnType() === PrimitiveType::STRING) {
            $supportsIContains = in_array(Operators::ICONTAINS, $schema->getFilterOperators(), true);
            $supportsContains = in_array(Operators::CONTAINS, $schema->getFilterOperators(), true);
            $supportsEqual = in_array(Operators::EQUAL, $schema->getFilterOperators(), true);

            $operator = null;
            if ($supportsIContains && ! $supportsContains) {
                $operator = Operators::ICONTAINS;
            } elseif ($supportsContains) {
                $operator = Operators::CONTAINS;
            } elseif ($supportsEqual) {
                $operator = Operators::EQUAL;
            }

            if ($operator) {
                return new ConditionTreeLeaf($field, $operator, $search);
            }
        }

        if ($schema->getColumnType() === PrimitiveType::UUID
            && Str::isUuid($search)
            && in_array(Operators::EQUAL, $schema->getFilterOperators(), true)
        ) {
            return new ConditionTreeLeaf($field, Operators::EQUAL, $search);
        }


        return null;
    }

    private function lenientFind(array $haystack, string $needle): string
    {
        $haystack = collect($haystack);

        return $haystack->firstWhere(fn ($value) => $value === trim($needle)) ??
            $haystack->firstWhere(fn ($value) => strtolower($value) === strtolower(trim($needle)));
    }
}
