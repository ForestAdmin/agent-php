<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class RowElement extends BaseFormElement
{
    public function __construct(
        protected array $fields,
        protected ?string $ifCondition = null
    ) {
        parent::__construct('Row', $ifCondition);
        $this->validateFieldsPresence();
        $this->validateNoLayoutSubfields($this->fields);
    }

    private function validateFieldsPresence(): void
    {
        if (empty($this->fields)) {
            throw new ForestException("Using 'fields' in a 'Row' configuration is mandatory");
        }
    }

    private function validateNoLayoutSubfields(array $fields): void
    {
        foreach ($fields as $field) {
            if ($field instanceof DynamicField && $field->getType() === 'Layout') {
                throw new ForestException("A 'Row' form element doesn't allow layout elements as subfields");
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
