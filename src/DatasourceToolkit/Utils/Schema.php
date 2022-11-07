<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

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

    public static function getToManyRelation(CollectionContract $collection, string $relationName): RelationSchema
    {
        if (! isset($collection->getFields()[$relationName])) {
            throw new ForestException("Relation $relationName not found");
        }

        $relation = $collection->getFields()[$relationName];

        if ($relation->getType() !== 'OneToMany' && $relation->getType() !== 'ManyToMany') {
            throw new ForestException("Relation $relationName has invalid type should be one of OneToMany or ManyToMany.");
        }

        return $relation;
    }
}
