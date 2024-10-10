<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField;

use DatasourceCustomizer\Decorators\Action\WidgetField\WidgetValidator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;

/**
 * @codeCoverageIgnore
 */
class TextAreaField extends DynamicField
{
    use Widget;

    private ?int $rows;

    public function __construct($options)
    {
        parent::__construct(...$options);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::STRING]]);
        $this->widget = 'TextArea';
        $this->rows = $options['rows'] ?? null;
    }

    public function getRows(): ?int
    {
        return $this->rows;
    }
}
