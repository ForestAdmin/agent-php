<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Str;

class GeneratorAction
{
    public static array $defaultFields = [
        [
            'field'        => 'Loading...',
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

        $fields = self::buildFields($collection, $name, $action);
        // todo => const fields = await SchemaGeneratorActions.buildFields(collection, name, schema);

        return [
            'id'         => "$collectionName-$index-$slug",
            'name'       => $name,
            'type'       => strtolower($action->getScope()),
            'baseUrl'    => null,
            'endpoint'   => "/forest/_actions/$collectionName/$index/$slug", // todo path.join('/', prefix, '_actions', collection.name, String(actionIndex), slug),
            'httpMethod' => 'POST',
            'redirect'   => null, // frontend ignores this attribute
            'download'   => $action->isGenerateFile(),
            'fields'     => $fields,
            'hooks'      => [
                'load'   => ! $action->isStaticForm(),
                // Always registering the change hook has no consequences, even if we don't use it.
                'change' => ['changeHook'],// todo question to devXP
            ],
        ];
    }

    public static function buildFieldSchema(Datasource $datasource, DynamicField $field)
    {
        $output = [
            'description' => $field->getDescription(),
            'isRequired'  => $field->isRequired(),
            'isReadOnly'  => $field->isReadOnly(),
            'field'       => $field->getLabel(),
            'value'       => ForestActionValueConverter::valueToForest($field), // to check
            'hook'        => 'changeHook',
        ];

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
            $output['type'] = '[' . Str::before($field->getType(), 'List') . ']';
        } else {
            $output['type'] = $field->getType();
        }

        if ($field->getType() === FieldType::ENUM || $field->getType() === FieldType::ENUM_LIST) {
            $output['enums'] = $field->getEnumValues();
        }

        return $output;
    }

    private static function buildFields(CollectionContract $collection, string $name, BaseAction $action): array
    {
        // We want the schema to be generated on usage => send dummy schema
        if (! $action->isStaticForm()) {
            return self::$defaultFields;
        }

        $fields = $action->getForm();

        if ($fields) {
//            // When sending to server, we need to rename 'value' into 'defaultValue'
//            // otherwise, it does not gets applied 🤷‍♂️
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
