<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\ActionField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\InputElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Str;

class GeneratorAction
{
    public static array $defaultFields = [
        [
            'field'        => 'Loading...',
            'label'        => 'Loading...',
            'type'         => 'String',
            'isReadOnly'   => true,
            'defaultValue' => 'Form is loading',
            'value'        => null,
            'description'  => '',
            'enums'        => null,
            'hook'         => null,
            'isRequired'   => false,
            'reference'    => null,
            'widget'       => null,
        ],
    ];

    public static function buildSchema(CollectionContract $collection, string $name): array
    {
        $collectionName = $collection->getName();
        /** @var BaseAction $action */
        $action = $collection->getActions()[$name];
        $index = $collection->getActions()->keys()->search($name);
        $slug = Str::slug($name);

        if($action->isStaticForm()) {
            $formElements = self::extractFieldsAndLayout($collection->getForm(null, $name));
            $fields = self::buildFields($formElements['fields']);
            $layout = $formElements['layout'];
        } else {
            $fields = self::$defaultFields;
            $layout = [];
        }

        return [
            'id'                => "$collectionName-$index-$slug",
            'name'              => $name,
            'submitButtonLabel' => $action->getSubmitButtonLabel(),
            'description'       => $action->getDescription(),
            'type'              => strtolower($action->getScope()),
            'baseUrl'           => null,
            'endpoint'          => "/forest/_actions/$collectionName/$index/$slug",
            'httpMethod'        => 'POST',
            'redirect'          => null, // frontend ignores this attribute
            'download'          => $action->isGenerateFile(),
            'fields'            => $fields,
            'layout'            => self::buildLayout($layout),
            'hooks'             => [
                'load'   => ! $action->isStaticForm(),
                // Always registering the change hook has no consequences, even if we don't use it.
                'change' => ['changeHook'],
            ],
        ];
    }

    public static function buildLayoutSchema($element): array
    {
        if ($element->getComponent() === 'Row') {
            return [
                ...$element->toArray(),
                'component' => Str::camel($element->getComponent()),
                'fields'    => array_map(fn ($f) => self::buildLayoutSchema($f), $element->getFields()),
            ];
        } elseif ($element->getComponent() === 'Page') {
            return [
                ...$element->toArray(),
                'component' => Str::camel($element->getComponent()),
                'elements'  => array_map(fn ($f) => self::buildLayoutSchema($f), $element->getElements()),
            ];
        }

        $result = [...$element->toArray(), 'component' => Str::camel($element->getComponent())];
        unset($result['type']);

        return $result;
    }

    public static function buildLayout($elements): array
    {
        $realLayoutElements = array_filter($elements, fn ($element) => $element !== 'Input');

        if (count($realLayoutElements) > 0) {
            foreach ($elements as &$element) {
                $element = self::buildLayoutSchema($element);
            }

            return $elements;
        }

        return [];
    }

    public static function extractFieldsAndLayout(array $form): array
    {
        $fields = [];
        $layout = [];

        foreach ($form as $element) {
            if($element->getType() === 'Layout') {
                if (in_array($element->getComponent(), ['Page', 'Row'])) {
                    $extract = self::extractFieldsAndLayoutForComponent($element);
                    $layout[] = $element;
                    $fields = [...$fields, ...$extract['fields']];
                } else {
                    $layout[] = $element;
                }
            } else {
                $fields[] = $element;
                $layout[] = new InputElement(fieldId: $element->getId());
            }
        }

        return compact('fields', 'layout');
    }

    public static function extractFieldsAndLayoutForComponent($element): array
    {
        $key = $element->getComponent() === 'Page' ? 'elements' : 'fields';

        $extract = self::extractFieldsAndLayout($element->__get($key));
        $element->__set($key, $extract['layout']);

        return $extract;
    }

    public static function buildFieldSchema(Datasource $datasource, ActionField $field)
    {
        $output = [
            'description' => $field->getDescription(),
            'isRequired'  => $field->isRequired(),
            'isReadOnly'  => $field->isReadOnly(),
            'field'       => $field->getId(),
            'label'       => $field->getLabel(),
            'value'       => ForestActionValueConverter::valueToForest($field), // to check
        ];

        if (method_exists($field, 'isWatchChanges') && $field->isWatchChanges()) {
            $output['hook'] = 'changeHook';
        }

        if ($field->getType() === FieldType::COLLECTION) {
            $collection = $datasource->getCollection($field->getCollectionName());
            [$pk] = SchemaUtils::getPrimaryKeys($collection);
            $pkSchema = $collection->getFields()[$pk];

            $output['type'] = $pkSchema->getColumnType();
            $output['reference'] = $collection->getName() . '.' . $pk;
        } elseif (
            in_array(
                $field->getType(),
                [FieldType::FILE_LIST, FieldType::ENUM_LIST, FieldType::NUMBER_LIST, FieldType::STRING_LIST],
                true
            )
        ) {
            $output['type'] = [Str::before($field->getType(), 'List')];
        } else {
            $output['type'] = $field->getType();
        }

        if ($field->getType() === FieldType::ENUM || $field->getType() === FieldType::ENUM_LIST) {
            $output['enums'] = $field->getEnumValues();
        }

        return $output;
    }

    public static function buildFields(?array $fields): array
    {
        if ($fields) {
            // When sending to server, we need to rename 'value' into 'defaultValue'
            // otherwise, it does not gets applied 🤷‍♂️
            return collect($fields)->map(
                static function ($field) {
                    $fieldSchema = self::buildFieldSchema(AgentFactory::get('datasource'), $field);
                    $fieldSchema['defaultValue'] = $fieldSchema['value'];
                    unset($fieldSchema['value']);

                    return $fieldSchema;
                }
            )->toArray();
        }

        return [];
    }
}
