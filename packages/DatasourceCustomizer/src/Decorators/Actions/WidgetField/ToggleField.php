<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField\WidgetValidator;

/**
 * @codeCoverageIgnore
 */
class ToggleField extends DynamicField
{
    use Widget;

    private string $onLabel;

    private string $offLabel;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::BOOLEAN]]);
        $this->widget = 'Toggle';
        $this->onLabel = $options['on_label'] ?? 'On';
        $this->offLabel = $options['off_label'] ?? 'Off';
    }

    public function getOnLabel(): string
    {
        return $this->onLabel;
    }

    public function getOffLabel(): string
    {
        return $this->offLabel;
    }
}
