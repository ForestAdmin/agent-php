<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

abstract class ManyRelationSchema extends RelationSchema
{
    protected string $foreignKey;

    protected string $foreignKeyTarget;
}
