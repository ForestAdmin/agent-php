<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as MainCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class Collection
{
    public static function getInverseRelation(CollectionContract $collection, string $relationName): ?string
    {
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
            $field->getThroughCollection() === $relationField->getThroughCollection()) {
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

    public static function getFieldSchema(CollectionContract $collection, string $fieldName): ColumnSchema|RelationSchema
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
            throw new ForestException('Relation not found ' . $collection->getName() . '.' . $associationName);
        }

        if ($relationSchema->getType() !== 'ManyToOne' && $relationSchema->getType() !== 'OneToOne') {
            throw new ForestException(
                'Unexpected field type ' . $relationSchema->getType() . ': '. $collection->getName() . '.' . $associationName,
            );
        }

        return self::getFieldSchema(AgentFactory::get('datasource')->getCollection($relationSchema->getForeignCollection()), substr($fieldName, $index + 1));
    }

    public static function getValue(CollectionContract $collection, Caller $caller, array $id, string $field)
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

        return $record[$field];
    }

    public static function getThroughTarget(CollectionContract $collection, string $relationName): ?string
    {
        $relation = $collection->getFields()[$relationName];
        if (! $relation instanceof ManyToManySchema) {
            throw new ForestException('Relation must be many to many');
        }

        $throughCollection = $collection->getDataSource()->getCollection($relation->getThroughCollection());
        foreach ($throughCollection->getFields() as $fieldName => $field) {
            if ($field instanceof ManyToOneSchema &&
                $field->getForeignCollection() === $relation->getForeignCollection() &&
                $field->getForeignKey() === $relation->getForeignKey() &&
                $field->getForeignKeyTarget() === $relation->getForeignKeyTarget()
            ) {
                return $fieldName;
            }
        }

        return null;
    }

    public static function listRelation(
        CollectionContract $collection,
        $id,
        string $relationName,
        Caller $caller,
        PaginatedFilter $foreignFilter,
        Projection $projection,
    ) {
        $relation = Schema::getToManyRelation($collection, $relationName);
        $foreignCollection = $collection->getDataSource()->getCollection($relation->getForeignCollection());
        if ($relation instanceof ManyToManySchema && $foreignFilter->isNestable()) {
            $foreignRelation = self::getThroughTarget($collection, $relationName);

            if ($foreignRelation) {
                $throughCollection = $collection->getDataSource()->getCollection($relation->getThroughCollection());

                $records = $throughCollection->list(
                    $caller,
                    FilterFactory::makeThroughFilter($collection, $id, $relationName, $caller, $foreignFilter),
                    $projection->nest($foreignRelation)
                );

                return collect($records)->map(fn ($record) => $record[$foreignRelation])->toArray();
            }
        }

        return $foreignCollection->list(
            $caller,
            FilterFactory::makeForeignFilter($collection, $id, $relationName, $caller, $foreignFilter),
            $projection
        );
    }

    public static function aggregateRelation(
        CollectionContract $collection,
        $id,
        string $relationName,
        Caller $caller,
        Filter $foreignFilter,
        Aggregation $aggregation,
        ?int $limit = null
    ) {
        $relation = Schema::getToManyRelation($collection, $relationName);
        $foreignCollection = $collection->getDataSource()->getCollection($relation->getForeignCollection());

        if ($relation instanceof ManyToManySchema && $foreignFilter->isNestable()) {
            $foreignRelation = self::getThroughTarget($collection, $relationName);
            if ($foreignRelation) {
                $throughCollection = $collection->getDataSource()->getCollection($relation->getThroughCollection());

                return $throughCollection->aggregate(
                    $caller,
                    FilterFactory::makeThroughFilter($collection, $id, $relationName, $caller, $foreignFilter),
                    $aggregation,
                    $limit
                );
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
