<?php

namespace ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\Widget;

class CheckboxGroupField extends DynamicField
{
    use Widget;

    private array $options;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::STRING_LIST, FieldType::NUMBER_LIST]]);
        $this->widget = 'CheckboxGroup';
        $this->options = $options['options'];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
