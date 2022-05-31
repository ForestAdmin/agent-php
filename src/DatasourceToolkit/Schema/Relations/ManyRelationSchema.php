<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

abstract class ManyRelationSchema extends RelationSchema
{
    public function __construct(
        protected string $foreignKey,
        protected string $foreignKeyTarget,
    ) {
    }
}
