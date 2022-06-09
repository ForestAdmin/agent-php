<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

class WebHookResult
{
    public function __construct(
        protected string $type = 'Error',
        protected string $url,
        protected string $method,
        protected array $headers,
        protected string $body,
    ) {
        parent::__construct($type);
    }
}
