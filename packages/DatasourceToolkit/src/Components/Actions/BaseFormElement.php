<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions;

class BaseFormElement
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
