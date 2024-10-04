<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField\WidgetField;

class RadioField extends DynamicField
{
    use Widget;

    private array $options;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::STRING_LIST]]);
        $this->widget = 'Radio';
        $this->options = $options['options'];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
