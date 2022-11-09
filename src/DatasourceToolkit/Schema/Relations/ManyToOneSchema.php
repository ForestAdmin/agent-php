<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class ManyToOneSchema extends ManyRelationSchema
{
    public function __construct(
        protected string $foreignKey,
        protected string $foreignKeyTarget,
        protected string $foreignCollection,
        protected string $inverseRelationName,
    ) {
        parent::__construct($foreignKey, $foreignKeyTarget, $foreignCollection, 'ManyToOne', $inverseRelationName);
    }

    public function getForeignCollection(): string
    {
        return $this->foreignCollection;
    }
}
