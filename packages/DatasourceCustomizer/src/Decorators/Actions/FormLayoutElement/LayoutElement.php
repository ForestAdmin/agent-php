<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseFormElement;

class LayoutElement extends BaseFormElement
{
    public function __construct(
        protected string $component,
        protected ?\Closure $if = null,
    ) {
        parent::__construct('Layout');
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getIf(): ?\Closure
    {
        return $this->if;
    }

    public function setIf(?string $if): void
    {
        $this->if = $if;
    }
}
