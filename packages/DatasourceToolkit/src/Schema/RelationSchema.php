<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

abstract class RelationSchema
{
    public function __construct(
        protected string $type,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }
}
