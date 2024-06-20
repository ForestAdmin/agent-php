<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

abstract class SingleRelationSchema extends RelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
        protected string $type,
    ) {
        parent::__construct($type);
    }

    public function setOriginKey(string $originKey): void
    {
        $this->originKey = $originKey;
    }

    public function getOriginKey(): string
    {
        return $this->originKey;
    }

    public function getOriginKeyTarget(): string
    {
        return $this->originKeyTarget;
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
