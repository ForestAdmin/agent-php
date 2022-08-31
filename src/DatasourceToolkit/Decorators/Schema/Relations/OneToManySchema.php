<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations;

class OneToManySchema extends SingleRelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
        protected string $inverseRelationName,
    ) {
        parent::__construct($originKey, $originKeyTarget, $foreignCollection, 'OneToMany', $inverseRelationName);
    }

    public function getForeignCollection(): string
    {
        return $this->foreignCollection;
    }
}
