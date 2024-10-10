<?php

namespace ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\Widget;

/**
 * @codeCoverageIgnore
 */
class DatePickerField extends DynamicField
{
    use Widget;

    private ?string $format;

    private ?string $min;

    private ?string $max;

    private ?string $step;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetValidator::validateArg($options, 'type', [
            'type'  => 'contains',
            'value' => [FieldType::DATE, FieldType::DATE_ONLY, FieldType::STRING],
        ]);
        $this->widget = 'DatePicker';
        $this->format = $options['format'] ?? null;
        $this->min = $options['min'] ?? null;
        $this->max = $options['max'] ?? null;
        $this->step = $options['step'] ?? null;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getMin(): ?string
    {
        return $this->min;
    }

    public function getMax(): ?string
    {
        return $this->max;
    }

    public function getStep(): ?string
    {
        return $this->step;
    }
}
