<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

class FileResult extends ActionResult
{
    public function __construct(
        protected string $type = 'File',
        protected string $mimeType,
        protected string $name,
        protected string $stream,
    ) {
        parent::__construct($type);
    }
}
