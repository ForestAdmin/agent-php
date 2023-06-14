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
    public function run(DatasourceCustomizer $datasourceCustomizer, ?CollectionCustomizer $collectionCustomizer = null, $options = []): void
    {
        if (! isset($options['name']) || ! isset($options['path'])) {
            throw new ForestException('The options parameter must contains the following keys: `name, path`');
        }

        if (! array_key_exists('readonly', $options)) {
            $options['readonly'] = false;
        }

        $name = $options['name'];
        $result = collect(explode(':', $options['path']))->reduce(function ($memo, $field) use ($datasourceCustomizer) {
            $collection = $datasourceCustomizer->getCollection($memo['collection']);

            if (! $collection->getSchema()->getFields()->get($field)) {
                throw new ForestException('Field ' . $field . ' not found in collection ' . $collection->getName());
            }

            $fieldSchema = $collection->getSchema()->getFields()[$field];
            if ($fieldSchema->getType() === 'Column') {
                return ['schema' => $fieldSchema];
            }

            if ($fieldSchema->getType() === 'ManyToOne' || $fieldSchema->getType() === 'OneToOne') {
                return ['collection' => $fieldSchema->getForeignCollection()];
            }
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

        if (! $options['readonly'] && ! $schema->isReadOnly()) {
            // @codeCoverageIgnoreStart
            $collectionCustomizer->replaceFieldWriting($name, function ($value) use ($options) {
                $path = explode(':', $options['path']);
                $writingPath = [];

                collect($path)->reduce(function ($nestedPath, $currentPath, $index) use ($path, $value) {
                    $nestedPath[$currentPath] = $index === count($path) - 1 ? $value : [];

                    return $nestedPath[$currentPath];
                }, $writingPath);

                return $writingPath;
            });
            // @codeCoverageIgnoreEnd
        }

        if (! $options['readonly'] && $schema->isReadOnly()) {
            throw new ForestException('Readonly option should not be false because the field ' . $options['path'] . ' is not writable');
        }

        // @codeCoverageIgnoreStart
        foreach ($schema->getFilterOperators() as $operator) {
            $collectionCustomizer->replaceFieldOperator(
                $name,
                $operator,
                fn ($value) => [
                    'field'    => $options['path'],
                    'operator' => $operator,
                    'value'    => $value,
                ]
            );
        }
        // @codeCoverageIgnoreEnd

        if ($schema->isSortable()) {
            $collectionCustomizer->replaceFieldSorting(
                $name,
                [
                    ['field' => $options['path'], 'ascending' => true],
                ]
            );
        }
    }
}
