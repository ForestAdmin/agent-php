<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\Relations;

class OneToOneSchema extends SingleRelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
        protected string $inverseRelationName,
    ) {
        parent::__construct($originKey, $originKeyTarget, $foreignCollection, 'OneToOne', $inverseRelationName);
    }

    public function getForeignCollection(): string
    {
        return $this->foreignCollection;
    }
}
