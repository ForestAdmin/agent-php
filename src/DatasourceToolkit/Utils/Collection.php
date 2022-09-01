<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as MainCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class Collection
{
    public static function getInverseRelation(MainCollection $collection, string $relationName): ?string
    {
        // TODO useful ? because we have the attribute inverseRelationName into our RelationSchema
        /** @var RelationSchema $relation */
        $relationField = $collection->getFields()->get($relationName);
        /** @var MainCollection $foreignCollection */
        $foreignCollection = AgentFactory::get('datasource')->getCollections()->first(fn ($item) => $item->getName() === $relationField->getForeignCollection());

        $inverse = $foreignCollection->getFields()
            ->filter(fn ($field) => is_a($field, RelationSchema::class))
            ->filter(
                fn ($field, $key) =>
                   $field->getForeignCollection() === $collection->getName() &&
                   (
                       (is_a($field, ManyToManySchema::class) &&
                           is_a($relationField, ManyToManySchema::class) &&
                           self::isManyToManyInverse($field, $relationField)) ||
                       (is_a($field, ManyToOneSchema::class) &&
                           (is_a($relationField, OneToOneSchema::class) || is_a($relationField, OneToManySchema::class)) &&
                           self::isManyToOneInverse($field, $relationField)) ||
                       ((is_a($field, OneToOneSchema::class) || is_a($field, OneToManySchema::class)) &&
                           is_a($relationField, ManyToOneSchema::class) && self::isOtherInverse($field, $relationField))
                   )
            )
            ->keys()
            ->first();

        return $inverse ?: null;
    }

    public static function isManyToManyInverse(ManyToManySchema $field, ManyToManySchema $relationField): bool
    {
        if ($field->getType() === 'ManyToMany' &&
            $relationField->getType() === 'ManyToMany' &&
            $field->getOriginKey() === $relationField->getForeignKey() &&
            $field->getThroughTable() === $relationField->getThroughTable() &&
            $field->getForeignKey() === $relationField->getOriginKey()) {
            return true;
        }

        return false;
    }

    public static function isManyToOneInverse(ManyToOneSchema $field, OnetoOneSchema|OneToManySchema $relation): bool
    {
        if ($field->getType() === 'ManyToOne' &&
            ($relation->getType() === 'OneToMany' || $relation->getType() === 'OneToOne') &&
            $field->getForeignKey() === $relation->getOriginKey()) {
            return true;
        }

        return false;
    }

    public static function isOtherInverse(OnetoOneSchema|OneToManySchema $field, ManyToOneSchema $relation): bool
    {
        if ($relation->getType() === 'ManyToOne' &&
            ($field->getType() === 'OneToMany' || $field->getType() === 'OneToOne') &&
            $field->getOriginKey() === $relation->getForeignKey()) {
            return true;
        }

        return false;
    }

    public static function getFieldSchema(MainCollection $collection, string $fieldName): ColumnSchema|RelationSchema
    {
        $fields = $collection->getFields();
        if (! $index = strpos($fieldName, ':')) {
            if (! $fields->get($fieldName)) {
                throw new ForestException('Column not found ' . $collection->getName() . '.' . $fieldName);
            }

            return $fields->get($fieldName);
        }

        $associationName = substr($fieldName, 0, $index);
        $relationSchema = $fields->get($associationName);

        if (! $relationSchema) {
            throw new Error('Relation not found ' . $collection->getName() . '.' . $associationName);
        }

        if ($relationSchema->getType() !== 'ManyToOne' && $relationSchema->getType() !== 'OneToOne') {
            throw new Error(
                'Unexpected field type ' . $relationSchema->getType() . ': '. $collection->getName() . '.' . $associationName,
            );
        }

        return self::getFieldSchema(AgentFactory::get('datasource')->getCollection($relationSchema->getForeignCollection()), substr($fieldName, $index + 1));
    }

    public static function getValue(MainCollection $collection, Caller $caller, array $id, string $field)
    {
        $index = array_search($field, Schema::getPrimaryKeys($collection), true);

        if ($index !== false) {
            return $id[$index];
        }

        $record = $collection->list(
            $caller,
            new Filter(conditionTree: ConditionTreeFactory::matchIds($collection, [$id])),
            new Projection([$field])
        );

        // todo this not work with all framework. example symfony -> $record->get{$field}()
        return $record[$field];
    }

    public static function getThroughTarget(MainCollection $collection, string $relationName): ?string
    {
        /** @var ManyToManySchema $relation */
        $relation = $collection->getFields()[$relationName];

        if ($relation->getType() !== 'ManyToMany') {
            throw new ForestException('Relation must be many to many');
        }

        return $relation->getForeignCollection();
    }

    public static function listRelation(
        MainCollection $collection,
        $id,
        string $relationName,
        Caller $caller,
        PaginatedFilter $foreignFilter,
        Projection $projection
    ) {
        $relation = Schema::getToManyRelation($collection, $relationName);
        $foreignCollection = $collection->getDataSource()->getCollection($relation->getForeignCollection());
        if ($relation->getType() === 'ManyToMany' && $foreignFilter->isNestable()) {
            $foreignRelation = self::getThroughTarget($collection, $relationName);
            $projection->push($relation->getForeignKey() . ':' . $relation->getForeignKeyTarget());

            if ($foreignRelation === $foreignCollection->getName()) {
                $records = $foreignCollection->list(
                    $caller,
                    FilterFactory::makeThroughFilter($collection, $id, $relationName, $caller, $foreignFilter),
                    $projection
                );

                return $records;
            }
        }

        return $foreignCollection->list(
            $caller,
            FilterFactory::makeForeignFilter($collection, $id, $relationName, $caller, $foreignFilter),
            $projection
        );
    }

    public static function aggregateRelation(
        MainCollection $collection,
        $id,
        string $relationName,
        Caller $caller,
        Filter $foreignFilter,
        Aggregation $aggregation,
        ?int $limit = null
    ) {
        $relation = Schema::getToManyRelation($collection, $relationName);
        $foreignCollection = $collection->getDataSource()->getCollection($relation->getForeignCollection());

        if ($relation->getType() === 'ManyToMany' && $foreignFilter->isNestable()) {
            $foreignRelation = self::getThroughTarget($collection, $relationName);
            $aggregation = $aggregation->override(field: $relation->getForeignKey() . ':' . $relation->getForeignKeyTarget());

            if ($foreignRelation === $foreignCollection->getName()) {
                $records = $foreignCollection->aggregate(
                    $caller,
                    FilterFactory::makeThroughFilter($collection, $id, $relationName, $caller, $foreignFilter),
                    $aggregation,
                    $limit
                );

                return $records;
            }
        }

        return $foreignCollection->aggregate(
            $caller,
            FilterFactory::makeForeignFilter($collection, $id, $relationName, $caller, $foreignFilter),
            $aggregation,
            $limit
        );
    }
}
