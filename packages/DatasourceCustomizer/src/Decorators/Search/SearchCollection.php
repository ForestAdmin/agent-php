<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Search;

use Closure;
use ForestAdmin\AgentPHP\Agent\Facades\Logger;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToOneSchema;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class SearchCollection extends CollectionDecorator
{
    private ?Closure $replacer = null;

    private bool $disabledSearch;

    public function __construct(CollectionDecorator|CollectionContract $childCollection, Datasource $dataSource)
    {
        parent::__construct($childCollection, $dataSource);
        $this->disabledSearch = $this->getSearchableFields(false)->isEmpty();
    }

    public function replaceSearch(Closure $replacer): void
    {
        $this->replacer = $replacer;
        $this->disabledSearch = false;
    }

    public function disableSearch(): void
    {
        $this->disabledSearch = true;
    }

    public function isSearchable(): bool
    {
        return ! $this->disabledSearch;
    }

    public function refineFilter(?Caller $caller, PaginatedFilter|Filter|null $filter): PaginatedFilter|Filter|null
    {
        if ($filter->getSearch() === null || trim($filter->getSearch()) === '') {
            return $filter->override(search: null);
        }

        if ($this->replacer || ! $this->childCollection->isSearchable()) {
            $tree = $this->defaultReplacer($filter->getSearch(), $filter->getSearchExtended());

            if ($this->replacer) {
                $plainTree = call_user_func($this->replacer, $filter->getSearch(), $filter->getSearchExtended(), new CollectionCustomizationContext($this, $caller));
                $tree = ConditionTreeFactory::fromArray($plainTree);
            }

            return $filter->override(
                conditionTree: ConditionTreeFactory::intersect([$filter->getConditionTree(), $tree]),
                search: null
            );
        }

        return $filter;
    }

    private function defaultReplacer(string $search, ?bool $searchExtended = null): ConditionTree
    {
        $searchableFields = $this->getSearchableFields($searchExtended);
        $conditions = $searchableFields->map(
            fn ($schema, $field) => $this->buildCondition($field, $schema, $search)
        )
            ->filter()
            ->toArray();

        return ConditionTreeFactory::union($conditions);
    }

    private function isSearchableField(ColumnSchema $field): bool
    {
        $operators = $field->getFilterOperators();

        if($field->getColumnType() === PrimitiveType::STRING) {
            return in_array(Operators::EQUAL, $operators, true) ||
                in_array(Operators::CONTAINS, $operators, true) ||
                in_array(Operators::ICONTAINS, $operators, true);
        }

        return in_array($field->getColumnType(), [PrimitiveType::UUID, PrimitiveType::ENUM, PrimitiveType::NUMBER], true) &&
            in_array(Operators::EQUAL, $operators, true);
    }

    private function getSearchableFields(?bool $searchExtended = null): IlluminateCollection
    {
        $fields = collect();
        foreach ($this->childCollection->getFields() as $name => $field) {
            if ($field instanceof ColumnSchema && $this->isSearchableField($field)) {
                $fields->put($name, $field);
            }

            if ($field instanceof PolymorphicManyToOneSchema && $searchExtended) {
                Logger::log(
                    'Debug',
                    "We're not searching through {$this->getName()}.{$name} because it's a polymorphic relation. " .
                    "You can override the default search behavior with 'replace_search'. " .
                    'See more: https://docs.forestadmin.com/developer-guide-agents-php/agent-customization/search'
                );

                continue;
            }

            if ($searchExtended && ($field instanceof ManyToOneSchema || $field instanceof OneToOneSchema || $field instanceof PolymorphicOneToOneSchema)) {
                $related = $this->childCollection->getDataSource()->getCollection($field->getForeignCollection());

                foreach ($related->getFields() as $subName => $subField) {
                    if ($subField instanceof ColumnSchema && $this->isSearchableField($subField)) {
                        $fields->put("$name:$subName", $subField);
                    }
                }
            }
        }

        return $fields;
    }

    private function buildCondition(string $field, ColumnSchema $schema, string $search): ?ConditionTree
    {
        if (is_numeric($search) && $schema->getColumnType() === PrimitiveType::NUMBER) {
            return new ConditionTreeLeaf($field, Operators::EQUAL, $search);
        }

        if ($schema->getColumnType() === PrimitiveType::ENUM) {
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

        if ($schema->getColumnType() === PrimitiveType::UUID && Str::isUuid($search)) {
            return new ConditionTreeLeaf($field, Operators::EQUAL, $search);
        }


        return null;
    }

    private function lenientFind(array $haystack, string $needle): ?string
    {
        $haystack = collect($haystack);

        return $haystack->first(fn ($value) => $value === trim($needle)) ??
            $haystack->first(fn ($value) => strtolower($value) === strtolower(trim($needle)));
    }
}
