<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;

class UserDropdownField extends DynamicField
{
    protected string $widget;

    public function __construct(array $options)
    {
        parent::__construct(...$options);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg(
            $options,
            'type',
            [
                'type'  => 'contains',
                'value' => [Types::FieldType::STRING, Types::FieldType::STRING_LIST],
            ]
        );

        $this->widget = 'UserDropdown';
    }
}
