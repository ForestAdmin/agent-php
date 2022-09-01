<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

// todo useful ?

class FileResult extends ActionResult
{
    public function __construct(
        protected string $mimeType,
        protected string $name,
        protected string $stream,
        protected string $type = 'File',
    ) {
        parent::__construct($type);
    }
}
