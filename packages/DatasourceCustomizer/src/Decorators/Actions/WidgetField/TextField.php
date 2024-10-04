<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField\WidgetField;

class TextField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::STRING]]);
        $this->widget = 'TextField';
    }
}
