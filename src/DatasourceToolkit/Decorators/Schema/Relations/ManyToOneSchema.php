<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations;

class ManyToOneSchema extends ManyRelationSchema
{
    public function __construct(
        protected string $foreignKey,
        protected string $foreignKeyTarget,
        protected string $foreignCollection,
    ) {
        parent::__construct($foreignKey, $foreignKeyTarget, $foreignCollection, 'ManyToOne');
    }

    public function getForeignCollection(): string
    {
        return $this->foreignCollection;
    }
}
