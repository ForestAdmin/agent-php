<?php

namespace ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\Widget;

/**
 * @codeCoverageIgnore
 */
class FilePickerField extends DynamicField
{
    use Widget;

    private ?array $extensions;

    private ?int $maxCount;

    private ?int $maxSizeMb;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetValidator::validateArg($options, 'options', ['type' => 'present']);
        WidgetValidator::validateArg($options, 'type', ['type' => 'contains', 'value' => [FieldType::FILE, FieldType::FILE_LIST]]);
        $this->widget = 'FilePicker';
        $this->extensions = $options['extensions'] ?? null;
        $this->maxSizeMb = $options['max_size_mb'] ?? null;
        $this->maxCount = $options['max_count'] ?? null;
    }

    public function getExtensions(): ?array
    {
        return $this->extensions;
    }

    public function getMaxCount(): ?int
    {
        return $this->maxCount;
    }

    public function getMaxSizeMb(): ?int
    {
        return $this->maxSizeMb;
    }
}
