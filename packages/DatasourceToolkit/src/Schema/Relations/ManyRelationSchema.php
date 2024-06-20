<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

abstract class ManyRelationSchema extends RelationSchema
{
    public function __construct(
        protected string $foreignKey,
        protected string $foreignKeyTarget,
        protected string $foreignCollection,
        protected string $type,
    ) {
        parent::__construct($type);
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getForeignKeyTarget(): string
    {
        return $this->foreignKeyTarget;
    }

    public function setForeignKey(string $foreignKey): void
    {
        $this->foreignKey = $foreignKey;
    }

    public function getForeignCollection(): string
    {
        return $this->foreignCollection;
    }

    public function setForeignCollection(string $foreignCollection): void
    {
        $this->foreignCollection = $foreignCollection;
    }
}
