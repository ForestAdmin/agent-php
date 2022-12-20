<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class OneToOneSchema extends SingleRelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
    ) {
        parent::__construct($originKey, $originKeyTarget, $foreignCollection, 'OneToOne');
    }

    public function getForeignCollection(): string
    {
        return $this->foreignCollection;
    }
}
