<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\HtmlBlockElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\InputElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\RowElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\SeparatorElement;

class ActionFieldFactory
{
    public static function buildField(DynamicField $field)
    {
        return new ActionField(
            type: $field['type'],
            label: $field['label'],
            description: $field['description'],
            isRequired: $field['isRequired'],
            isReadOnly: $field['isReadOnly'],
            value: $field['value'],
            enumValues: $field['enumValues'],
            collectionName: $field['collectionName'],
        );
    }

    public static function build($element)
    {
        if ($element->getType() === 'Layout') {
            return self::buildLayoutElement($element);
        }

        return self::buildField($element);
    }

    public static function buildLayoutElement($element)
    {
        switch ($element->getComponent()) {
            case 'Separator':
                return new SeparatorElement();
            case 'HtmlBlock':
                return new HtmlBlockElement(content: $element->getContent());
            case 'Input':
                return new InputElement(fieldId: $element->getFieldId());
            case 'Row':
                if (empty($element->getFields())) {
                    return null;
                }

                return new RowElement(fields: array_map(fn ($field) => self::build($field), $element->getFields()));
        }
    }
}
