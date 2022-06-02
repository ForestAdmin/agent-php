<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as MainCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class Collection
{
    public static function getInverseRelation(MainCollection $collection, string $relationName): ?string
    {
        /** @var RelationSchema $relation */
        $relationField = $collection->getFields()->get($relationName);
        /** @var MainCollection $foreignCollection */
        $foreignCollection = $collection->getDataSource()->getCollections()->first(fn ($item) => $item->getName() === $relationField->getForeignCollection());
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
            $field->getThroughCollection() === $relationField->getThroughCollection() &&
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
}
