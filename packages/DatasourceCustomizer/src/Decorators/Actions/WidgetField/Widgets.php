<?php

namespace ForestAdminDatasourceCustomizer\Decorators\Action\WidgetField;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class WidgetField
{
    public static function validateArg($options, $attribute, $rule)
    {
        switch ($rule[$attribute]) {
            case 'contains':
                if (! in_array($options[$attribute], $rule['value'], true)) {
                    throw new ForestException("'$attribute' must have a value included in [" . implode(',', $rule['value']) . "]");
                }

                break;

            case 'present':
                if (! array_key_exists($attribute, $options)) {
                    throw new ForestException("key '$attribute' must be defined");
                }

                break;
        }
    }
}

class TimePickerField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => ['Time']]);
        $this->widget = 'TimePicker';
    }
}

class AddressAutocompleteField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => ['String']]);
        $this->widget = 'AddressAutocomplete';
    }
}

class CheckboxField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::BOOLEAN]]);
        $this->widget = 'Checkbox';
    }
}

class CheckboxGroupField extends DynamicField
{
    use Widget;
    private array $options;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::STRING_LIST, Types::FieldType::NUMBER_LIST]]);
        $this->widget = 'CheckboxGroup';
        $this->options = $options['options'];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

class ColorPickerField extends DynamicField
{
    use Widget;
    private ?bool $enableOpacity;
    private ?array $quickPalette;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'enable_opacity', ['type' => 'contains', 'value' => [Types::FieldType::STRING]]);
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
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::NUMBER]]);
        WidgetField::validateArg($options, 'currency', ['type' => 'present']);
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
        WidgetField::validateArg($options, 'type', [
            'type'  => 'contains',
            'value' => [Types::FieldType::DATE, Types::FieldType::DATE_ONLY, Types::FieldType::STRING],
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

class DropdownField extends DynamicField
{
    use Widget;
    private array $options;
    private ?bool $search;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', [
            'type'  => 'contains',
            'value' => [Types::FieldType::DATE, Types::FieldType::DATE_ONLY, Types::FieldType::STRING, Types::FieldType::STRING_LIST],
        ]);
        $this->widget = 'Dropdown';
        $this->options = $options['options'];
        $this->search = $options['search'] ?? null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getSearch(): ?bool
    {
        return $this->search;
    }
}

class FilePickerField extends DynamicField
{
    use Widget;
    private ?array $extensions;
    private ?int $maxCount;
    private ?int $maxSizeMb;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::FILE, Types::FieldType::FILE_LIST]]);
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

class NumberInputField extends DynamicField
{
    use Widget;
    private ?int $step;
    private ?int $min;
    private ?int $max;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::NUMBER]]);
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

class JsonEditorField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::STRING, Types::FieldType::STRING_LIST]]);
        $this->widget = 'JsonEditor';
    }
}

class PercentageInputField extends DynamicField
{
    use Widget;
    private ?int $step;
    private ?int $min;
    private ?int $max;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::NUMBER]]);
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

class RadioField extends DynamicField
{
    use Widget;
    private array $options;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::STRING_LIST]]);
        $this->widget = 'Radio';
        $this->options = $options['options'];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

class RatingField extends DynamicField
{
    use Widget;
    private int $maxRating;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'max_rating', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::NUMBER]]);
        $this->widget = 'Rating';
        $this->maxRating = $options['max_rating'];
    }

    public function getMaxRating(): int
    {
        return $this->maxRating;
    }
}

class SwitchField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::BOOLEAN]]);
        $this->widget = 'Switch';
    }
}

class TagInputField extends DynamicField
{
    use Widget;
    private array $options;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'options', ['type' => 'present']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::STRING_LIST]]);
        $this->widget = 'TagInput';
        $this->options = $options['options'];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

class TextField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::STRING]]);
        $this->widget = 'TextField';
    }
}

class TextAreaField extends DynamicField
{
    use Widget;
    private ?int $rows;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::STRING]]);
        $this->widget = 'TextArea';
        $this->rows = $options['rows'] ?? null;
    }

    public function getRows(): ?int
    {
        return $this->rows;
    }
}

class ToggleField extends DynamicField
{
    use Widget;
    private string $onLabel;
    private string $offLabel;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::BOOLEAN]]);
        $this->widget = 'Toggle';
        $this->onLabel = $options['on_label'] ?? 'On';
        $this->offLabel = $options['off_label'] ?? 'Off';
    }

    public function getOnLabel(): string
    {
        return $this->onLabel;
    }

    public function getOffLabel(): string
    {
        return $this->offLabel;
    }
}

class UrlField extends DynamicField
{
    use Widget;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::STRING]]);
        $this->widget = 'Url';
    }
}

class WysiwygField extends DynamicField
{
    use Widget;
    private ?array $toolbarOptions;

    public function __construct($options)
    {
        parent::__construct($options['type'], $options['label']);
        WidgetField::validateArg($options, 'type', ['type' => 'contains', 'value' => [Types::FieldType::STRING]]);
        $this->widget = 'Wysiwyg';
        $this->toolbarOptions = $options['toolbar_options'] ?? null;
    }

    public function getToolbarOptions(): ?array
    {
        return $this->toolbarOptions;
    }
}
