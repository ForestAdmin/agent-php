<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

abstract class ActionResult
{
    public function __construct(
        protected string $type
    ) {
    }
}
