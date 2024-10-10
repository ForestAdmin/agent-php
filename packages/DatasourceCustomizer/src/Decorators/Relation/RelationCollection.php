<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Relation;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\SingleRelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record as RecordUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class RelationCollection extends CollectionDecorator
{
    protected array $relations = [];

    public function addRelation(string $name, array $partialJoin): void
    {
        $relation = $this->relationWithOptionalFields($partialJoin);
        $this->checkForeignKeys($relation);
        $this->checkOriginKeys($relation);

        $this->relations[$name] = $relation;
        $this->markSchemaAsDirty();
    }

    public function refineSchema(IlluminateCollection $childSchema): IlluminateCollection
    {
        foreach ($this->relations as $name => $relation) {
            $childSchema->put($name, $relation);
        }

        return $childSchema;
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $newFilter = $this->refineFilter($caller, $filter);
        $newProjection = $projection->replaceItem(fn ($field) => $this->rewriteField($field))->withPks($this);
        $records = $this->childCollection->list($caller, $newFilter, $newProjection);

        if ($newProjection->toArray() === $projection->toArray()) {
            return $records;
        }

        $records = $this->reprojectInPlace($caller, $records, $projection);

        return $projection->apply($records)->all();
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        $newFilter = $this->refineFilter($caller, $filter);

        // No emulated relations are used in the aggregation
        if ($aggregation->getProjection()->relations()->every(fn ($relation, $prefix) => ! isset($this->relations[$prefix]))) {
            return $this->childCollection->aggregate($caller, $newFilter, $aggregation, $limit);
        }

        $filter = new PaginatedFilter(
            $filter->getConditionTree(),
            $filter->getSearch(),
            $filter->getSearchExtended(),
            $filter->getSegment()
        );

        // Fallback to full emulation.
        return $aggregation->apply(
            $this->list($caller, $filter, $aggregation->getProjection()),
            $caller->getTimezone(),
            $limit,
        );
    }

    public function refineFilter(?Caller $caller, PaginatedFilter|Filter|null $filter): PaginatedFilter|Filter|null
    {
        if (! method_exists($filter, 'getSort')) {
            return $filter->override(
                conditionTree: $filter->getConditionTree()?->replaceLeafs(
                    fn ($leaf) => $this->rewriteLeaf($caller, $leaf),
                )
            );
        }

        return $filter->override(
            conditionTree: $filter->getConditionTree()?->replaceLeafs(
                fn ($leaf) => $this->rewriteLeaf($caller, $leaf),
            ),
            // Replace sort in emulated relations to
            // - sorting by the fk of the relation for many to one
            // - removing the sort altogether for one to one
            //
            // This is far from ideal, but the best that can be done without taking a major
            // performance hit.
            // Customers which want proper sorting should enable emulation in the associated
            // middleware
            sort: $filter->getSort()?->replaceClauses(
                fn ($clause) => $this->rewriteField($clause['field'])->map(
                    fn ($field) => array_merge($clause, ['field' => $field])
                )
            )
        );
    }

    private function rewriteField(string $field): IlluminateCollection
    {
        $prefix = Str::before($field, ':');
        $schema = $this->getFields()[$prefix];
        if ($schema instanceof ColumnSchema || $schema instanceof PolymorphicManyToOneSchema) {
            return collect($field);
        }

        $relation = $this->dataSource->getCollection($schema->getForeignCollection());
        $result = collect();

        if (! isset($this->relations[$prefix])) {
            $result = $relation->rewriteField(Str::after($field, ':'))
                ->map(fn ($subField) => "$prefix:$subField");
        } elseif ($schema instanceof ManyToOneSchema) {
            $result->push($schema->getForeignKey());
        } elseif (
            $schema instanceof OneToOneSchema ||
            $schema instanceof OneToManySchema ||
            $schema instanceof ManyToManySchema
        ) {
            $result->push($schema->getOriginKeyTarget());
        }

        return $result;
    }

    private function rewriteLeaf(Caller $caller, ConditionTreeLeaf $leaf): ConditionTree
    {
        $prefix = Str::before($leaf->getField(), ':');
        $schema = $this->getFields()[$prefix];

        if ($schema instanceof ColumnSchema) {
            return $leaf;
        }

        /** @var RelationCollection $relation */
        $relation = $this->dataSource->getCollection($schema->getForeignCollection());
        if (! isset($this->relations[$prefix])) {
            return ($relation->rewriteLeaf($caller, $leaf->unnest()))->nest($prefix);
        } elseif ($schema instanceof ManyToOneSchema) {
            $records = $relation->list(
                $caller,
                new PaginatedFilter(conditionTree: $leaf->unnest()),
                new Projection($schema->getForeignKeyTarget())
            );

            return new ConditionTreeLeaf(
                field: $schema->getForeignKey(),
                operator: Operators::IN,
                value: [
                    ...collect($records)->map(
                        fn ($record) => RecordUtils::getFieldValue($record, $schema->getForeignKeyTarget())
                    )
                        ->filter()
                        ->toArray(),
                ]
            );
        } elseif ($schema instanceof OneToOneSchema) {
            $records = $relation->list(
                $caller,
                new PaginatedFilter(conditionTree: $leaf->unnest()),
                new Projection($schema->getOriginKey())
            );

            return new ConditionTreeLeaf(
                field: $schema->getOriginKeyTarget(),
                operator: Operators::IN,
                value: [
                    ...collect($records)->map(
                        fn ($record) => RecordUtils::getFieldValue($record, $schema->getOriginKey())
                    )
                        ->filter()
                        ->toArray(),
                ]
            );
        }

        return $leaf;
    }

    private function checkForeignKeys(RelationSchema $relation): void
    {
        if ($relation instanceof ManyToOneSchema || $relation instanceof ManyToManySchema) {
            self::checkKeys(
                $relation instanceof ManyToManySchema
                    ? $this->dataSource->getCollection($relation->getThroughCollection())
                    : $this,
                $this->dataSource->getCollection($relation->getForeignCollection()),
                $relation->getForeignKey(),
                $relation->getForeignKeyTarget()
            );
        }
    }

    private function checkOriginKeys(RelationSchema $relation): void
    {
        if (
            $relation instanceof OneToManySchema ||
            $relation instanceof OneToOneSchema ||
            $relation instanceof ManyToManySchema
        ) {
            self::checkKeys(
                $relation instanceof ManyToManySchema
                    ? $this->dataSource->getCollection($relation->getThroughCollection())
                    : $this->dataSource->getCollection($relation->getForeignCollection()),
                $this,
                $relation->getOriginKey(),
                $relation->getOriginKeyTarget()
            );
        }
    }

    private function relationWithOptionalFields(array $partialJoin): RelationSchema
    {
        $target = $this->dataSource->getCollection($partialJoin['foreignCollection']);
        // remove null values
        $partialJoin = array_filter($partialJoin);

        return match ($partialJoin['type']) {
            'ManyToOne'  => new ManyToOneSchema(
                foreignKey: $partialJoin['foreignKey'],
                foreignKeyTarget: Arr::get($partialJoin, 'foreignKeyTarget', Schema::getPrimaryKeys($target)[0]),
                foreignCollection: $partialJoin['foreignCollection'],
            ),
            'OneToOne'   => new OneToOneSchema(
                originKey: $partialJoin['originKey'],
                originKeyTarget: Arr::get($partialJoin, 'originKeyTarget', Schema::getPrimaryKeys($this)[0]),
                foreignCollection: $partialJoin['foreignCollection'],
            ),
            'OneToMany'  => new OneToManySchema(
                originKey: $partialJoin['originKey'],
                originKeyTarget: Arr::get($partialJoin, 'originKeyTarget', Schema::getPrimaryKeys($this)[0]),
                foreignCollection: $partialJoin['foreignCollection'],
            ),
            'ManyToMany' => new ManyToManySchema(
                originKey: $partialJoin['originKey'],
                originKeyTarget: Arr::get($partialJoin, 'originKeyTarget', Schema::getPrimaryKeys($this)[0]),
                foreignKey: $partialJoin['foreignKey'],
                foreignKeyTarget: Arr::get($partialJoin, 'foreignKeyTarget', Schema::getPrimaryKeys($target)[0]),
                foreignCollection: $partialJoin['foreignCollection'],
                throughCollection: $partialJoin['throughCollection'],
            )
        };
    }

    private static function checkKeys(CollectionContract $owner, CollectionContract $targetOwner, string $keyName, string $targetName): void
    {
        self::checkColumn($owner, $keyName);
        self::checkColumn($targetOwner, $targetName);

        /** @var ColumnSchema $key */
        $key = $owner->getFields()[$keyName];
        /** @var ColumnSchema $target */
        $target = $targetOwner->getFields()[$targetName];

        if ($key->getColumnType() !== $target->getColumnType()) {
            throw new ForestException(
                'Types from ' . $owner->getName() . '.' . $keyName . ' and ' .
                $targetOwner->getName() . '.' . $targetName . ' do not match.'
            );
        }
    }

    private static function checkColumn(CollectionContract $owner, string $name): void
    {
        if (! isset($owner->getFields()[$name]) || ! $owner->getFields()[$name] instanceof ColumnSchema) {
            throw new ForestException('Column not found: ' . $owner->getName() . '.' . $name);
        }

        if (! in_array(Operators::IN, $owner->getFields()[$name]->getFilterOperators(), true)) {
            throw new ForestException('Column does not support the In operator: ' . $owner->getName() . '.' . $name);
        }
    }

    private function reprojectInPlace(Caller $caller, array $records, Projection $projection): array
    {
        foreach ($projection->relations() as $prefix => $subProjection) {
            $records = $this->reprojectRelationInPlace($caller, $records, $prefix, $subProjection);
        }

        return $records;
    }

    private function reprojectRelationInPlace(Caller $caller, array $records, string $name, Projection $projection): array
    {
        /** @var RelationSchema $schema */
        $schema = $this->getFields()[$name];
        $association = $this->dataSource->getCollection($schema->getForeignCollection());
        if (! isset($this->relations[$name])) {
            return $association->reprojectInPlace(
                $caller,
                collect($records)->map(fn ($record) => collect($record)->filter()->toArray())->toArray(),
                $projection
            );
        } elseif ($schema instanceof ManyToOneSchema) {
            $ids = collect($records)->map(fn ($record) => $record[$schema->getForeignKey()])->filter();
            $subFilter = new PaginatedFilter(
                new ConditionTreeLeaf($schema->getForeignKeyTarget(), Operators::IN, $ids->all())
            );
            $subRecords = $association->list($caller, $subFilter, $projection->union([$schema->getForeignKeyTarget()]));
            foreach ($records as &$record) {
                $record[$name] = collect($subRecords)->first(fn ($subRecord) => $subRecord[$schema->getForeignKeyTarget()] === $record[$schema->getForeignKey()]);
            }
        } elseif ($schema instanceof SingleRelationSchema) {
            $ids = collect($records)->map(fn ($record) => $record[$schema->getOriginKeyTarget()])->filter();
            $subFilter = new PaginatedFilter(
                new ConditionTreeLeaf($schema->getOriginKey(), Operators::IN, $ids->all())
            );
            $subRecords = $association->list($caller, $subFilter, $projection->union([$schema->getOriginKey()]));
            foreach ($records as &$record) {
                $record[$name] = collect($subRecords)->first(fn ($sr) => $sr[$schema->getOriginKey()] === $record[$schema->getOriginKeyTarget()]);
            }
        }

        return $records;
    }
}
