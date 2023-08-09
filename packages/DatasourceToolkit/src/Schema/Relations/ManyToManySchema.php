<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class ManyToManySchema extends ManyRelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignKey,
        protected string $foreignKeyTarget,
        protected string $foreignCollection,
        protected string $throughCollection,
    ) {
        parent::__construct($foreignKey, $foreignKeyTarget, $foreignCollection, 'ManyToMany');
    }

    public function setOriginKey(string $originKey): void
    {
        $this->originKey = $originKey;
    }

    public function getThroughCollection(): string
    {
        return $this->throughCollection;
    }

    public function setThroughCollection(string $throughCollection): void
    {
        $this->throughCollection = $throughCollection;
    }

    public function getOriginKey(): string
    {
        return $this->originKey;
    }

    public function getOriginKeyTarget(): string
    {
        return $this->originKeyTarget;
    }
}
