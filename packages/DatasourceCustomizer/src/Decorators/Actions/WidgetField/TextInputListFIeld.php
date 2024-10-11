<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;

class TextInputListField extends DynamicField
{
    protected string $widget;

    protected ?bool $allowDuplicates;

    protected ?bool $allowEmptyValues;

    protected ?bool $enableReorder;

    public function __construct(array $options)
    {
        parent::__construct(...$options);
        WidgetField::validateArg(
            $options,
            'type',
            [
                'type'  => 'contains',
                'value' => [Types::FieldType::STRING_LIST],
            ]
        );

        $this->widget = 'TextInputList';
        $this->allowDuplicates = $options['allow_duplicates'] ?? null;
        $this->allowEmptyValues = $options['allow_empty_values'] ?? null;
        $this->enableReorder = $options['enable_reorder'] ?? null;
    }
}
