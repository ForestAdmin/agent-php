<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Relation;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
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

    public function getFields(): IlluminateCollection
    {
        $fields = parent::getFields();
        foreach ($this->relations as $name => $relation) {
            $fields->put($name, $relation);
        }

        return $fields;
    }

    public function refineFilter(Caller $caller, PaginatedFilter|Filter|null $filter): PaginatedFilter|Filter|null
    {
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
                    fn ($field) => [...$clause, $field]
                )
            )
        );
    }

    private function rewriteField(string $field): IlluminateCollection
    {
        $prefix = Str::before($field, ':');
        $schema = $this->getFields()[$prefix];
        if ($schema instanceof ColumnSchema) {
            return [$field];
        }

        $relation = $this->dataSource->getCollection($schema->getForeignCollection);
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
        // todo
    }

    private function checkForeignKeys(RelationSchema $relation): void
    {
        if ($relation instanceof ManyToOneSchema || $relation instanceof ManyToManySchema) {
            self::checkKeys(
                $this,
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
                $this->dataSource->getCollection($relation->getForeignCollection()),
                $this,
                $relation->getOriginKey(),
                $relation->getOriginKeyTarget()
            );
        }
    }

    private function relationWithOptionalFields(array $partialJoin): RelationSchema
    {
        $target = $this->dataSource->getCollection($partialJoin['foreignCollection']);

        return match ($partialJoin['type']) {
            'ManyToOne' => new ManyToOneSchema(
                foreignKey: $partialJoin['foreignKey'],
                foreignKeyTarget: Arr::get($partialJoin, 'foreignKeyTarget', Schema::getPrimaryKeys($target)[0]),
                foreignCollection: $partialJoin['foreignCollection'],
                inverseRelationName:''
            ),
            'OneToOne'  => new OneToOneSchema(
                originKey: $partialJoin['originKey'],
                originKeyTarget: Arr::get($partialJoin, 'foreignKeyTarget', Schema::getPrimaryKeys($this)[0]),
                foreignCollection: $partialJoin['foreignCollection'],
                inverseRelationName: ''
            ),
            'OneToMany' => new OneToManySchema(
                originKey: $partialJoin['originKey'],
                originKeyTarget: Arr::get($partialJoin, 'foreignKeyTarget', Schema::getPrimaryKeys($this)[0]),
                foreignCollection: $partialJoin['foreignCollection'],
                inverseRelationName: ''
            ),
            'ManyToMany' => new ManyToManySchema(
                originKey: $partialJoin['originKey'],
                originKeyTarget: Arr::get($partialJoin, 'originKeyTarget', Schema::getPrimaryKeys($this)[0]),
                throughTable: '',
                foreignKey: $partialJoin['foreignKey'],
                foreignKeyTarget: Arr::get($partialJoin, 'foreignKeyTarget', Schema::getPrimaryKeys($target)[0]),
                foreignCollection: $partialJoin['foreignCollection'],
                inverseRelationName: '',
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
                $target->getName() . '.' . $targetName . 'do not match.'
            );
        }
    }

    private static function checkColumn(CollectionContract $owner, string $name): void
    {
        if (! ($column = $owner->getFields()[$name]) || ! $column instanceof ColumnSchema) {
            throw new ForestException('Column not found: ' . $owner->getName() . '.' . $name);
        }

        if (! in_array(Operators::IN, $column->getFilterOperators(), true)) {
            throw new ForestException('Column does not support the In operator: ' . $owner->getName() . '.' . $name);
        }
    }
}
