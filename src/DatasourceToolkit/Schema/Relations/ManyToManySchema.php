<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class ManyToManySchema extends ManyRelationSchema
{
    public function __construct(
        protected string $foreignKey,
        protected string $foreignKeyTarget,
        protected string $throughCollection,
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
    ) {
        parent::__construct($foreignKey, $foreignKeyTarget, $foreignCollection, 'ManyToMany');
    }

    public function getThroughCollection(): string
    {
        return $this->throughCollection;
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
