<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Plugins;

use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;

class AddExternalRelation implements Plugin
{
    public function run(DatasourceCustomizer $datasourceCustomizer, ?CollectionCustomizer $collectionCustomizer = null, $options = []): void
    {
        $primaryKeys = SchemaUtils::getPrimaryKeys($collectionCustomizer->getCollection());

        if (! isset($options['name']) || ! isset($options['schema']) || ! isset($options['listRecords'])) {
            throw new ForestException('The options parameter must contains the following keys: `name, schema, listRecords`');
        }

        $collectionCustomizer->addField(
            $options['name'],
            new ComputedDefinition(
                columnType: [$options['schema']],
                dependencies: $options['dependencies'] ?? $primaryKeys,
                values: fn ($records, $context) => collect($records)->map(fn ($record) => $options['listRecords']($record, $context))
            )
        );
    }
}
