<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

class SuccessResult
{
    protected string $invalidated;

    public function __construct(
        protected string $type = 'Success',
        protected string $message,
        protected string $format,
    ) {
        parent::__construct($type);
    }

    public function getInvalidated(): string
    {
        return $this->invalidated;
    }

    public function setInvalidated(string $invalidated): SuccessResult
    {
        $this->invalidated = $invalidated;
        return $this;
    }
}
