<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseFormElement;

class LayoutElement extends BaseFormElement
{
    public function __construct(
        protected string $component,
        protected ?string $ifCondition = null,
        array $extraArguments = []
    ) {
        parent::__construct('Layout', $extraArguments);
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getIfCondition(): ?string
    {
        return $this->ifCondition;
    }
}
