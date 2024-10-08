<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseFormElement;

class BaseLayoutElement extends BaseFormElement
{
    /**
     * @param string $component
     */
    public function __construct(
        protected string $component,
    ) {
        parent::__construct(type: 'Layout');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function setComponent(string $component): void
    {
        $this->component = $component;
    }
}
