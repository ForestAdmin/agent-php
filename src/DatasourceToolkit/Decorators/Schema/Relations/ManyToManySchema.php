<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations;

class ManyToManySchema extends ManyRelationSchema
{
    public function __construct(
        protected string $foreignKey,
        protected string $foreignKeyTarget,
        protected string $throughTable,
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
    ) {
        parent::__construct($foreignKey, $foreignKeyTarget, $foreignCollection, 'ManyToMany');
    }

    public function getThroughTable(): string
    {
        return $this->throughTable;
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
