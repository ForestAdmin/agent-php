<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class OneToManySchema extends SingleRelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
    ) {
        parent::__construct($originKey, $originKeyTarget, $foreignCollection, 'OneToMany');
    }

    public function getForeignCollection(): string
    {
        return $this->foreignCollection;
    }
}
