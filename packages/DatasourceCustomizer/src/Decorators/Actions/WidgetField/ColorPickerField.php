<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\Widget;

/**
 * @codeCoverageIgnore
 */
class ColorPickerField extends DynamicField
{
    use Widget;

    private ?bool $enableOpacity;

    private ?array $quickPalette;

    public function __construct($options)
    {
        parent::__construct(...$options);
        WidgetValidator::validateArg($options, 'enable_opacity', ['type' => 'contains', 'value' => [FieldType::STRING]]);
        $this->widget = 'ColorPicker';
        $this->enableOpacity = $options['enable_opacity'] ?? null;
        $this->quickPalette = $options['quick_palette'] ?? null;
    }

    public function getEnableOpacity(): ?bool
    {
        return $this->enableOpacity;
    }

    public function getQuickPalette(): ?array
    {
        return $this->quickPalette;
    }
}
