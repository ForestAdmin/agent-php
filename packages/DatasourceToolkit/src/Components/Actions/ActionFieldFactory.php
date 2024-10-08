<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\InputElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\SeparatorElement;

class ActionFieldFactory
{
    public static function buildFromDynamicField(DynamicField $field)
    {
        return new ActionField(
            type: $field->getType(),
            label: $field->getLabel(),
            description: $field->getDescription(),
            isRequired: $field->isRequired(),
            isReadOnly: $field->isReadOnly(),
            value: $field->getValue(),
            enumValues: $field->getEnumValues(),
            collectionName: $field->getCollectionName(),
        );
    }

    public static function build($element)
    {
        if ($element['type'] === 'Layout') {
            return self::buildLayoutElement($element);
        }

        return self::buildFromDynamicField($element);
    }

    public static function buildLayoutElement($element)
    {
        switch ($element['component']) {
            case 'Separator':
                return new SeparatorElement();
            case 'Input':
                return new InputElement(fieldId: $element['fieldId']);
        }
    }
}
