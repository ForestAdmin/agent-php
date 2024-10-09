<?php

namespace ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\Widget;

/**
 * @codeCoverageIgnore
 */
class CurrencyInputField extends DynamicField
{
    use Widget;
    private string $currency;
    private ?string $base;
    private ?int $min;
    private ?int $max;
    private ?int $step;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::NUMBER]]);
        WidgetValidator::validateArg($options, 'currency', ['type' => 'present']);
        $this->widget = 'CurrencyInput';
        $this->currency = $options['currency'];
        $this->base = $options['base'] ?? 'Unit';
        $this->min = $options['min'] ?? null;
        $this->max = $options['max'] ?? null;
        $this->step = $options['step'] ?? null;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBase(): ?string
    {
        return $this->base;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function getStep(): ?int
    {
        return $this->step;
    }
}
