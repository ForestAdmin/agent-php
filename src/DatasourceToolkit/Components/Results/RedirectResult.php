<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

class RedirectResult extends ActionResult
{
    public function __construct(
        protected string $type = 'Redirect',
        protected string $path,
    ) {
        parent::__construct($type);
    }
}
