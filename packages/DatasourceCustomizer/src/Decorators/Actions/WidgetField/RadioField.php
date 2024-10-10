<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use DatasourceCustomizer\Decorators\Action\WidgetField\WidgetValidator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;

/**
 * @codeCoverageIgnore
 */
class RadioField extends DynamicField
{
    use Widget;

    private array $options;

    public function __construct($options)
    {
        parent::__construct(...$options);
        WidgetValidator::validateArg($options, 'options', ['type' => 'present']);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::STRING_LIST]]);
        $this->widget = 'Radio';
        $this->options = $options['options'];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
