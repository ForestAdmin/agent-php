<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField;

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
        parent::__construct(...$options);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => ['Time']]);
        $this->widget = 'TimePicker';
    }
}
