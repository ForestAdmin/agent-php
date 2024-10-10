<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseFormElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

/**
 * @codeCoverageIgnore
 */
class RowElement extends BaseFormElement
{
    public function __construct(
        protected array $fields,
        array $extraArguments = []
    ) {
        parent::__construct('Row', $extraArguments);
        $this->validateFieldsPresence($extraArguments);
        $this->validateNoLayoutSubfields($extraArguments['fields'] ?? []);
        $this->fields = $this->instantiateSubfields($extraArguments['fields'] ?? []);
    }

    private function validateFieldsPresence(array $options): void
    {
        if (! array_key_exists('fields', $options)) {
            throw new ForestException("Using 'fields' in a 'Row' configuration is mandatory");
        }
    }

    private function validateNoLayoutSubfields(array $fields): void
    {
        foreach ($fields as $field) {
            if (($field instanceof DynamicField && $field->getType() === 'Layout') ||
                (is_array($field) && ($field['type'] ?? '') === 'Layout')) {
                throw new ForestException("A 'Row' form element doesn't allow layout elements as subfields");
            }
        }
    }

    private function instantiateSubfields(array $fields): array
    {
        return array_map(fn ($field) => new DynamicField(...$field), $fields);
    }
}
