<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

abstract class RelationSchema
{
    protected string $type;

    abstract public function getFormat(): array;
}
