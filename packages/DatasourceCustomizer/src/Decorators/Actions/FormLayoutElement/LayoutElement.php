<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseFormElement;

class LayoutElement extends BaseFormElement
{
    public function __construct(
        protected string $component,
        protected ?string $if = null,
        array $extraArguments = []
    ) {
        parent::__construct('Layout', $extraArguments);
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getIf(): ?string
    {
        return $this->if;
    }

    public function setIf(?string $if): void
    {
        $this->if = $if;
    }
}
