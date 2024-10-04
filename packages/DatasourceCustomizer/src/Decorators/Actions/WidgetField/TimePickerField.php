<?php

namespace ForestAdmin\AgentPHP\ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\Widget;

/**
 * @codeCoverageIgnore
 */
class TimePickerField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => ['Time']]);
        $this->widget = 'TimePicker';
    }
}
