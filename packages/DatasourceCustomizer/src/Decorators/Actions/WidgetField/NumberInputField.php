<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use DatasourceCustomizer\Decorators\Action\WidgetField\WidgetValidator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;

/**
 * @codeCoverageIgnore
 */
class NumberInputField extends DynamicField
{
    use Widget;

    private ?int $step;

    private ?int $min;

    private ?int $max;

    public function __construct($options)
    {
        parent::__construct(...$options);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::NUMBER]]);
        $this->widget = 'NumberInput';
        $this->step = $options['step'] ?? null;
        $this->min = $options['min'] ?? null;
        $this->max = $options['max'] ?? null;
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }
}
