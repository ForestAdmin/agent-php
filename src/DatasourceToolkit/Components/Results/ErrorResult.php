<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

// todo useful ?

class ErrorResult extends ActionResult
{
    public function __construct(
        protected string $message,
        protected string $type = 'Error',
    ) {
        parent::__construct($type);
    }
}
