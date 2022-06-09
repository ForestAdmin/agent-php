<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

class RedirectResult
{
    public function __construct(
        protected string $type = 'Redirect',
        protected string $path,
    ) {
        parent::__construct($type);
    }
}
