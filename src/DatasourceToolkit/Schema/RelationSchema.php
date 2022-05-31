<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

abstract class RelationSchema
{
    protected string $type;

    public function getType(): string
    {
        return $this->type;
    }
}
