<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\Relations;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\RelationSchema;

abstract class SingleRelationSchema extends RelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
        protected string $type,
        protected string $inverseRelationName,
    ) {
        parent::__construct($foreignCollection, $type, $inverseRelationName);
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
