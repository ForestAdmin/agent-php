<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class Record
{
    public static function getPrimaryKeys(Collection $schema, array $record): array
    {
        return collect(Schema::getPrimaryKeys($schema))->map(
            fn ($pk) => $record[$pk] ?? throw new ForestException("Missing primary key: $pk")
        )->toArray();
    }
<<<<<<< HEAD
=======

    public static function getToManyRelation(Collection $collection, string $relationName): RelationSchema
    {
        $relation = $collection->getFields()[$relationName];

        if (! $relation) {
            throw new ForestException("Relation $relationName not found");
        }

        if ($relation->getType() !== 'OneToMany' && $relation->getType() !== 'ManyToMany') {
            throw new ForestException("Relation $relationName has invalid type should be one of OneToMany or ManyToMany.");
        }

        return $relation;
    }
>>>>>>> 96c9102 (feat: update ConditionTreeFactory )
}
