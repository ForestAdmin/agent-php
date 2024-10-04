<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\AddressAutocompleteField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\CheckboxField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\CheckboxGroupField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\ColorPickerField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\CurrencyInputField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\DatePickerField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\DropdownField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\FilePickerField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Action\WidgetField\TimePickerField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\HtmlBlockElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\PageElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\RowElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\SeparatorElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\JsonEditorField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\NumberInputField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\NumberInputListField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\RadioGroupField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\RichTextField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\TextAreaField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\TextInputField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\TextInputListField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\WidgetField\UserDropdownField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class FormFactory
{
    public static function buildElements(?array $form): ?array
    {
        return collect($form)->map(function ($field) {
            if (is_array($field)) {
                if (isset($field['widget'])) {
                    return self::buildWidget($field);
                } elseif ($field['type'] === 'Layout') {
                    return self::buildLayoutElement($field);
                } else {
                    return new DynamicField(...$field);
                }
            }

            if ($field instanceof RowElement) {
                return self::buildElements($field->getFields());
            }

            if ($field instanceof PageElement) {
                return self::buildElements($field->getElements());
            }

            return $field;
        })->toArray();
    }

    public static function buildWidget(array $field)
    {
        return match ($field['widget']) {
            'AddressAutocomplete' => new AddressAutocompleteField(...$field),
            'Checkbox'            => new CheckboxField(...$field),
            'CheckboxGroup'       => new CheckboxGroupField(...$field),
            'ColorPicker'         => new ColorPickerField(...$field),
            'CurrencyInput'       => new CurrencyInputField(...$field),
            'DatePicker'          => new DatePickerField(...$field),
            'Dropdown'            => new DropdownField(...$field),
            'FilePicker'          => new FilePickerField(...$field),
            'JsonEditor'          => new JsonEditorField(...$field),
            'NumberInput'         => new NumberInputField(...$field),
            'NumberInputList'     => new NumberInputListField(...$field),
            'RadioGroup'          => new RadioGroupField(...$field),
            'RichText'            => new RichTextField(...$field),
            'TextArea'            => new TextAreaField(...$field),
            'TextInput'           => new TextInputField(...$field),
            'TextInputList'       => new TextInputListField(...$field),
            'TimePicker'          => new TimePickerField(...$field),
            'UserDropdown'        => new UserDropdownField(...$field),
            default               => throw new ForestException("Unknown widget type: {$field['widget']}")
        };
    }

    public static function buildLayoutElement(array $field)
    {
        return match ($field['component']) {
            'Separator' => new SeparatorElement(...$field),
            'HtmlBlock' => new HtmlBlockElement(...$field),
            'Row'       => new RowElement($field),
            'Page'      => new PageElement($field),
            default     => throw new ForestException("Unknown component type: {$field['component']}")
        };
    }
}
