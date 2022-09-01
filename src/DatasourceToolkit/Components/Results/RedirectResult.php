<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

// todo useful ?

class RedirectResult extends ActionResult
{
    public function __construct(
        protected string $path,
        protected string $type = 'Redirect',
    ) {
        parent::__construct($type);
    }
}
