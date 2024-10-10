<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class RowElement extends LayoutElement
{
    public function __construct(
        protected array $fields,
        protected ?\Closure $if = null
    ) {
        parent::__construct('Row', $if);
        $this->validateFieldsPresence();
        $this->validateSubfields();
    }

    private function validateFieldsPresence(): void
    {
        if (empty($this->fields)) {
            throw new ForestException("Using 'fields' in a 'Row' configuration is mandatory");
        }
    }

    private function validateSubfields(): void
    {
        foreach ($this->fields as $field) {
            if (! $field instanceof DynamicField) {
                throw new ForestException("A field must be an instance of DynamicField");
            }
        }
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }
}
