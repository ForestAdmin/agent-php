<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results;

// todo useful ?

class SuccessResult extends ActionResult
{
    protected string $invalidated;

    public function __construct(
        protected string $message,
        protected string $format,
        protected string $type = 'Success',
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
