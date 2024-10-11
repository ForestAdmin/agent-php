<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\InputElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\SeparatorElement;

class ActionFieldFactory
{
    public static function buildField(array $field)
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
        if ($element['type'] === 'Layout') {
            return self::buildLayoutElement($element);
        }

        return self::buildField($element);
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
