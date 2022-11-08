<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\Relations;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\RelationSchema;

abstract class ManyRelationSchema extends RelationSchema
{
    public function __construct(
        protected string $foreignKey,
        protected string $foreignKeyTarget,
        protected string $foreignCollection,
        protected string $type,
        protected string $inverseRelationName,
    ) {
        parent::__construct($foreignCollection, $type, $inverseRelationName);
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getForeignKeyTarget(): string
    {
        return $this->foreignKeyTarget;
    }
}
