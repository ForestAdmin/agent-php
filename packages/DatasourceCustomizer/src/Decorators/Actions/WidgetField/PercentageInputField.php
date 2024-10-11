<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField\WidgetValidator;

/**
 * @codeCoverageIgnore
 */
class PercentageInputField extends DynamicField
{
    use Widget;

    private ?int $step;

    private ?int $min;

    private ?int $max;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::NUMBER]]);
        $this->widget = 'PercentageInput';
        $this->min = $options['min'] ?? null;
        $this->max = $options['max'] ?? null;
        $this->step = $options['step'] ?? null;
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
