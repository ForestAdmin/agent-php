<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

abstract class RelationSchema
{
    public function __construct(
        protected string $foreignCollection,
        protected string $type,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
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
