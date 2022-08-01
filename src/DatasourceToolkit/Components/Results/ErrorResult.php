<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

class ErrorResult extends ActionResult
{
    // TODO may extends Exception ? is yes transform this Class to Exception
    public function __construct(
        protected string $type = 'Error',
        protected string $message,
    ) {
        parent::__construct($type);
    }
}
