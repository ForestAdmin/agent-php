<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Sort;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record as RecordUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;
use Illuminate\Support\Str;

class SortCollection extends CollectionDecorator
{
    private array $sorts = [];

    public function emulateFieldSorting(string $name): void
    {
        $this->replaceFieldSorting($name, null);
    }

    public function replaceFieldSorting(string $name, ?array $equivalentSort): void
    {
        FieldValidator::validate($this, $name);

        if (! isset($this->childCollection->getFields()[$name])) {
            throw new ForestException('Cannot replace sort on relation');
        }

        $this->sorts[$name] = ! empty($equivalentSort) ? new Sort($equivalentSort) : null;
        $this->markSchemaAsDirty();
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        /** @var PaginatedFilter $childFilter */
        $childFilter = $filter->override(
            sort: $filter->getSort()?->replaceClauses(fn ($clause) => $this->rewritePlainSortClause($clause))
        );

        if (! $childFilter->getSort()?->some(fn ($clause) => $this->isEmulated($clause['field']))) {
            return $this->childCollection->list($caller, $childFilter, $projection);
        }

        // Fetch the whole collection, but only with the fields we need to sort
        $referenceRecords = $this->childCollection->list(
            $caller,
            $childFilter->override(sort: null, page: null),
            $childFilter->getSort()->getProjection()->withPks($this)
        );
        $referenceRecords = $childFilter->getSort()->apply($referenceRecords);

        if ($childFilter->getPage()) {
            $referenceRecords = $childFilter->getPage()->apply($referenceRecords);
        }

        // We now have the information we need to sort by the field
        $newFilter = new PaginatedFilter(
            conditionTree: ConditionTreeFactory::matchRecords($this, $referenceRecords)
        );

        $records = $this->childCollection->list($caller, $newFilter, $projection->withPks($this));
        $records = $this->sortRecords($referenceRecords, $records);

        return $projection->apply($records)->toArray();
    }

    private function sortRecords(array $referenceRecords, array $records): array
    {
        $positionById = [];
        $sorted = [];
        foreach (array_values($referenceRecords) as $index => $record) {
            $id = implode('|', RecordUtils::getPrimaryKeys($this, $record));
            $positionById[$id] = $index;
        }

        foreach ($records as $record) {
            $id = implode('|', RecordUtils::getPrimaryKeys($this, $record));
            $sorted[$positionById[$id]] = $record;
        }

        return $sorted;
    }

    private function rewritePlainSortClause(array $clause): Sort
    {
        if (Str::contains($clause['field'], ':')) {
            $prefix = Str::before($clause['field'], ':');
            /** @var RelationSchema $relation */
            $relation = $this->getFields()[$prefix];
            $association = $this->getDataSource()->getCollection($relation->getForeignCollection());

            return (new Sort([$clause]))
                ->unnest()
                ->replaceClauses(fn ($subClause) => $association->rewritePlainSortClause($subClause))
                ->nest($prefix);
        }

        if (isset($this->sorts[$clause['field']])) {
            /** @var Sort $equivalentSort */
            $equivalentSort = $this->sorts[$clause['field']];
            if (! $clause['ascending']) {
                $equivalentSort = $equivalentSort->inverse();
            }

            return $equivalentSort->replaceClauses(fn ($subClause) => $this->rewritePlainSortClause($subClause));
        }

        return new Sort([$clause]);
    }

    private function isEmulated(string $path): bool
    {
        if (! Str::contains($path, ':')) {
            return array_key_exists($path, $this->sorts);
        }

        /** @var RelationSchema $relation */
        $relation = $this->getFields()[Str::before($path, ':')];
        $association = $this->dataSource->getCollection($relation->getForeignCollection());

        return $association->isEmulated(Str::after($path, ':'));
    }
}
