<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;

class RadioGroupField extends DynamicField
{
    use Widget;

    protected array $optionRadios;

    public function __construct(array $options)
    {
        parent::__construct(...$options);

        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg(
            $options,
            'type',
            [
                'type'  => 'contains',
                'value' => [
                    FieldType::DATE,
                    FieldType::DATE_ONLY,
                    FieldType::NUMBER,
                    FieldType::STRING,
                ],
            ]
        );

        $this->setWidget('RadioGroup');
        $this->optionRadios = $options['optionRadios'];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
