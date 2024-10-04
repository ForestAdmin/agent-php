<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;

class RichTextField extends DynamicField
{
    use Widget;

    public function __construct(array $options)
    {
        parent::__construct(...$options);

        WidgetField::validate_arg($options, 'options', ['type' => 'present']);
        WidgetField::validate_arg(
            $options,
            'type',
            [
                'type'  => 'contains',
                'value' => [FieldType::STRING],
            ]
        );

        $this->widget = 'RichText';
    }
}
