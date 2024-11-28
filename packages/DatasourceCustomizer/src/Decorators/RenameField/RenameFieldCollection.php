<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class RenameFieldCollection extends CollectionDecorator
{
    protected array $fromChildCollection = [];
    protected array $toChildCollection = [];

    public function renameField(string $currentName, string $newName): void
    {
        if ($this->getFields()->get($currentName) === null) {
            throw new ForestException("No such field '$currentName'");
        }

        $initialName = $currentName;

        // Revert previous renaming (avoids conflicts and need to recurse on this.toSubCollection).
        if (isset($this->toChildCollection[$currentName])) {
            $childName = $this->toChildCollection[$currentName];
            unset($this->toChildCollection[$currentName], $this->fromChildCollection[$childName]);
            $initialName = $childName;
            $this->markAllSchemaAsDirty();
        }

        // Do not update arrays if renaming is a no-op (ie: customer is cancelling a previous rename).
        if ($initialName !== $newName) {
            $this->fromChildCollection[$initialName] = $newName;
            $this->toChildCollection[$newName] = $initialName;
            $this->markAllSchemaAsDirty();
        }
    }

    public function refineSchema(IlluminateCollection $childSchema): IlluminateCollection
    {
        $fields = collect();

        # we don't handle schema modification for polymorphic many to one and reverse relations because
        # we forbid to rename foreign key and type fields on polymorphic many to one
        foreach ($childSchema as $oldName => $schema) {
            if ($schema instanceof ManyToOneSchema) {
                $relation = $this->dataSource->getCollection($schema->getForeignCollection());
                $schema->setForeignKey($this->fromChildCollection[$schema->getForeignKey()] ?? $schema->getForeignKey());
                $schema->setForeignKeyTarget(
                    $relation->fromChildCollection[$schema->getForeignKeyTarget()] ?? $schema->getForeignKeyTarget()
                );
            } elseif ($schema instanceof OneToManySchema || $schema instanceof OneToOneSchema) {
                /** @var self $relation */
                $relation = $this->dataSource->getCollection($schema->getForeignCollection());
                $schema->setOriginKey($relation->fromChildCollection[$schema->getOriginKey()] ?? $schema->getOriginKey());
                $schema->setOriginKeyTarget(
                    $this->fromChildCollection[$schema->getOriginKeyTarget()] ?? $schema->getOriginKeyTarget()
                );
            } elseif ($schema instanceof ManyToManySchema) {
                /** @var self $through */
                $through = $this->dataSource->getCollection($schema->getThroughCollection());
                $schema->setForeignKey($through->fromChildCollection[$schema->getForeignKey()] ?? $schema->getForeignKey());
                $schema->setOriginKey($through->fromChildCollection[$schema->getOriginKey()] ?? $schema->getOriginKey());
                $schema->setOriginKeyTarget(
                    $this->fromChildCollection[$schema->getOriginKeyTarget()] ?? $schema->getOriginKeyTarget()
                );
                $schema->setForeignKeyTarget(
                    $this->fromChildCollection[$schema->getForeignKeyTarget()] ?? $schema->getForeignKeyTarget()
                );
            }

            $fields->put($this->fromChildCollection[$oldName] ?? $oldName, $schema);
        }

        return $fields;
    }

    public function create(Caller $caller, array $data)
    {
        $newRecord = $this->childCollection->create($caller, $this->recordToChildCollection($data));

        return $this->recordFromChildCollection($newRecord);
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $childProjection = $projection->replaceItem(fn ($field) => $this->pathToChildCollection($field));
        $records = $this->childCollection->list($caller, $this->refineFilter($caller, $filter), $childProjection);
        if ($childProjection->diff($projection)->isEmpty()) {
            return $records;
        }

        return collect($records)->map(fn ($record) => $this->recordFromChildCollection($record))->toArray();
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        return $this->childCollection->update($caller, $this->refineFilter($caller, $filter), $this->recordToChildCollection($patch));
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        $rows = $this->childCollection->aggregate(
            $caller,
            $this->refineFilter($caller, $filter),
            $aggregation->replaceFields(fn ($field) => $this->pathToChildCollection($field)),
            $limit
        );

        return collect($rows)->map(
            fn ($row) => [
                'value' => $row['value'],
                'group' => collect($row['group'] ?? [])->reduce(
                    fn ($memo, $value, $key) => array_merge($memo, [$this->pathFromChildCollection($key) => $value]),
                    []
                ),
            ]
        )
            ->toArray();
    }

    protected function refineFilter(?Caller $caller, Filter|PaginatedFilter|null $filter): Filter|PaginatedFilter|null
    {
        if ($filter instanceof PaginatedFilter) {
            return $filter?->override(
                conditionTree: $filter->getConditionTree()?->replaceFields(fn ($field) => $this->pathToChildCollection($field)),
                sort: $filter->getSort()?->replaceClauses(fn ($clause) => [
                    [
                        'field'     => $this->pathToChildCollection($clause['field']),
                        'ascending' => $clause['ascending'],
                    ],
                ])
            );
        } else {
            return $filter?->override(
                conditionTree: $filter->getConditionTree()?->replaceFields(fn ($field) => $this->pathToChildCollection($field)),
            );
        }
    }

    /** Convert field path from child collection to this collection */
    private function pathFromChildCollection(string $childPath): string
    {
        if (Str::contains($childPath, ':')) {
            $childField = Str::before($childPath, ':');
            $thisField = $this->fromChildCollection[$childField] ?? $childField;
            /** @var RelationSchema $relationSchema */
            $relationSchema = $this->getFields()[$thisField];
            /** @var self $relation */
            $relation = $this->getDataSource()->getCollection($relationSchema->getForeignCollection());

            return "$thisField:" . $relation->pathFromChildCollection(Str::after($childPath, ':'));
        }

        return $this->fromChildCollection[$childPath] ?? $childPath;
    }

    /** Convert field path from this collection to child collection */
    private function pathToChildCollection(string $path): string
    {
        if (Str::contains($path, ':')) {
            $relationName = Str::before($path, ':');
            /** @var RelationSchema $relationSchema */
            $relationSchema = $this->getFields()[$relationName];
            if ($relationSchema->getType() === 'PolymorphicManyToOne') {
                $relationName = $this->toChildCollection[$relationName];

                return "$relationName:" . Str::after($path, ':');
            } else {
                /** @var self $relation */
                $relation = $this->getDataSource()->getCollection($relationSchema->getForeignCollection());
                $childField = $this->toChildCollection[$relationName] ?? $relationName;

                return "$childField:" . $relation->pathToChildCollection(Str::after($path, ':'));
            }
        }

        return $this->toChildCollection[$path] ?? $path;
    }

    /** Convert record from this collection to the child collection */
    private function recordToChildCollection(array $thisRecord): array
    {
        $childRecord = [];
        foreach ($thisRecord as $thisField => $value) {
            $childRecord[$this->toChildCollection[$thisField] ?? $thisField] = $value;
        }

        return $childRecord;
    }

    /** Convert record from the child collection to this collection */
    private function recordFromChildCollection(array $childRecord): array
    {
        $thisRecord = [];

        foreach ($childRecord as $childField => $value) {
            $thisField = $this->fromChildCollection[$childField] ?? $childField;
            $fieldSchema = $this->getFields()[$thisField];

            // Perform the mapping, recurse for relations.
            if ($fieldSchema instanceof ColumnSchema || $value === null || $fieldSchema->getType() === 'PolymorphicManyToOne' || $fieldSchema->getType() === 'PolymorphicOneToOne') {
                $thisRecord[$thisField] = $value;
            } else {
                /** @var self $relation */
                $relation = $this->getDataSource()->getCollection($fieldSchema->getForeignCollection());
                $thisRecord[$thisField] = $relation->recordFromChildCollection($value);
            }
        }

        return $thisRecord;
    }

    private function markAllSchemaAsDirty()
    {
        foreach ($this->dataSource->getCollections() as $collection) {
            $collection->markSchemaAsDirty();
        }
    }
}
