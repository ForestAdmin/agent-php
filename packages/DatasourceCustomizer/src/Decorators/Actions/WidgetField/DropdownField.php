<?php

namespace ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\Widget;

/**
 * @codeCoverageIgnore
 */
class DropdownField extends DynamicField
{
    use Widget;
    private array $options;
    private ?bool $search;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetValidator::validateArg($options, 'options', ['type' => 'present']);
        WidgetValidator::validateArg($options, 'type', [
            'type'  => 'contains',
            'value' => [FieldType::DATE, FieldType::DATE_ONLY, FieldType::STRING, FieldType::STRING_LIST],
        ]);
        $this->widget = 'Dropdown';
        $this->options = $options['options'];
        $this->search = $options['search'] ?? null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getSearch(): ?bool
    {
        return $this->search;
    }
}
