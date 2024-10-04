<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;

class NumberInputListField extends DynamicField
{
    use Widget;

    protected ?bool $allow_duplicates;

    protected ?bool $allow_empty_values;

    protected ?bool $enable_reorder;

    protected ?int $min;

    protected ?int $max;

    protected ?float $step;

    public function __construct(array $options)
    {
        parent::__construct(...$options);

        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg(
            $options,
            'type',
            [
                'type'  => 'contains',
                'value' => [FieldType::NUMBER_LIST],
            ]
        );

        $this->setWidget('NumberInputList');
        $this->allow_duplicates = $options['allow_duplicates'] ?? null;
        $this->allow_empty_values = $options['allow_empty_values'] ?? null;
        $this->enable_reorder = $options['enable_reorder'] ?? null;
        $this->min = $options['min'] ?? null;
        $this->max = $options['max'] ?? null;
        $this->step = $options['step'] ?? null;
    }

    public function getAllowDuplicates(): ?bool
    {
        return $this->allow_duplicates;
    }

    public function getAllowEmptyValues(): ?bool
    {
        return $this->allow_empty_values;
    }

    public function getEnableReorder(): ?bool
    {
        return $this->enable_reorder;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function getStep(): ?float
    {
        return $this->step;
    }
}
