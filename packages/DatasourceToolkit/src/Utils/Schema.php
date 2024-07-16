<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class Schema
{
    public static function getPrimaryKeys(CollectionContract $schema): array
    {
        return $schema
            ->getFields()
            ->keys()
            ->filter(
                static function ($fieldName) use ($schema) {
                    /** @var ColumnSchema|RelationSchema $field */
                    $field = $schema->getFields()[$fieldName];

                    return $field->getType() === 'Column' && $field->isPrimaryKey();
                }
            )
            ->values()
            ->all();
    }

    public static function isPrimaryKey(CollectionContract $schema, string $fieldName): bool
    {
        /** @var ColumnSchema|RelationSchema $field */
        $field = $schema->getFields()[$fieldName];

        return $field->getType() === 'Column' && $field->isPrimaryKey();
    }

    public static function isForeignKey(CollectionContract $schema, string $name): bool
    {
        $field = $schema->getFields()[$name];

        return (
            $field->getType() === 'Column' &&
            $schema->getFields()->some(
                static function ($relation) use ($name) {
                    return $relation->getType() === 'ManyToOne' && $relation->getForeignKey() === $name;
                }
            )
        );
    }

    public static function getToManyRelation(CollectionContract $collection, string $relationName): RelationSchema
    {
        if (! isset($collection->getFields()[$relationName])) {
            throw new ForestException("Relation $relationName not found");
        }

        $relation = $collection->getFields()[$relationName];

        if (! in_array($relation->getType(), ['OneToMany', 'ManyToMany', 'PolymorphicOneToMany'])) {
            throw new ForestException("Relation $relationName has invalid type should be one of OneToMany, ManyToMany or PolymorphicOneToMany.");
        }

        return $relation;
    }
}
