<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

// todo useful ?

class WebHookResult extends ActionResult
{
    public function __construct(
        protected string $url,
        protected string $method,
        protected array $headers,
        protected string $body,
        protected string $type = 'Error',
    ) {
        parent::__construct($type);
    }
}
