<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\HtmlBlockElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\InputElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\PageElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\RowElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\SeparatorElement;

class ActionFieldFactory
{
    public static function buildField(DynamicField $field)
    {
        return new ActionField(
            type: $field->getType(),
            label: $field->getLabel(),
            id: $field->getId(),
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
            case 'Page':
                if (empty($element->getElements())) {
                    return null;
                }

                return new PageElement(
                    elements: array_map(fn ($element) => self::build($element), $element->getElements()),
                    nextButtonLabel: $element->getNextButtonLabel(),
                    previousButtonLabel: $element->getPreviousButtonLabel()
                );
        }
    }
}
