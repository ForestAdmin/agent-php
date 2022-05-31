<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

abstract class SingleRelationSchema extends RelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
    ) {
    }
}
