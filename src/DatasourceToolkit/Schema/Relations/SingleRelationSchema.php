<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

abstract class SingleRelationSchema extends RelationSchema
{
    protected string $originKey;

    protected string $originKeyTarget;
}
