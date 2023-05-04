<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Plugins;

use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record as RecordUtils;

class ImportField implements Plugin
{
    public function run(DatasourceCustomizer $datasourceCustomizer, CollectionCustomizer $collectionCustomizer, $options): void
    {
        if (! isset($options['name']) || ! isset($options['path'])) {
            throw new ForestException('The options parameter must contains the following keys: `name, path`');
        }

        $name = $options['name'];
        $result = collect(explode(':', $options['path']))->reduce(function ($memo, $field) use ($datasourceCustomizer) {
            $collection = $datasourceCustomizer->getCollection($memo['collection']);

            if (! $collection->getSchema()->getFields()[$field]) {
                throw new ForestException("Field $field not found in collection $collection->getName()");
            }

            $fieldSchema = $collection->getSchema()->getFields()[$field];
            if ($fieldSchema->getType() === 'Column') {
                return ['schema' => $fieldSchema];
            }

            if ($fieldSchema->getType() === 'ManyToOne' || $fieldSchema->getType() === 'OneToOne') {
                return ['collection' => $fieldSchema->getForeignCollection()];
            }

            throw new ForestException("Invalid options['path']");
        }, ['collection' => $collectionCustomizer->getName()]);

        /** @var ColumnSchema $schema */
        $schema = $result['schema'];

        $collectionCustomizer->addField(
            $name,
            new ComputedDefinition(
                columnType: $schema->getColumnType(),
                dependencies: [$options['path']],
                values: fn ($records) => collect($records)->map(fn ($record) => RecordUtils::getFieldValue($record, $options['path'])),
                defaultValue: $schema->getDefaultValue(),
                enumValues: $schema->getEnumValues(),
            )
        );

        if ((array_key_exists('readonly', $options) && ! $options['readonly']) && ! $schema->isReadOnly()) {
            $collectionCustomizer->replaceFieldWriting($name, function ($value) use ($options) {
                $path = explode(':', $options['path']);
                $writingPath = [];

                collect($path)->reduce(function ($nestedPath, $currentPath, $index) use ($path, $value) {
                    $nestedPath[$currentPath] = $index === count($path) - 1 ? $value : [];

                    return $nestedPath[$currentPath];
                }, $writingPath);

                return $writingPath;
            });
        }
    }
}
