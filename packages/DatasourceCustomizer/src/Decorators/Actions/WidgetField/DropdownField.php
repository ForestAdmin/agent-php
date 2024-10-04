<?php

namespace ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\Widget;

class DropdownField extends DynamicField
{
    use Widget;
    private array $options;
    private ?bool $search;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', [
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
