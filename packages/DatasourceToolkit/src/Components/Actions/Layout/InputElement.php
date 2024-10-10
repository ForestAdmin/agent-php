<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout;

class InputElement extends BaseLayoutElement
{
    public function __construct(
        protected string $fieldId
    ) {
        parent::__construct(component: 'Input');
    }

    public function getFieldId(): string
    {
        return $this->fieldId;
    }
}
