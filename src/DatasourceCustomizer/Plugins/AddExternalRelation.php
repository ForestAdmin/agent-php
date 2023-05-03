<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Plugins;

use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;

class AddExternalRelation extends Plugin
{
    public function __construct(DatasourceCustomizer $datasourceCustomizer, CollectionCustomizer $collectionCustomizer, array $options)
    {
        $primaryKeys = SchemaUtils::getPrimaryKeys($collectionCustomizer->getSchema());

        if (! $options['name'] || ! $options['schema'] || ! $options['listRecords']) {
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

        parent::__construct($datasourceCustomizer, $collectionCustomizer, $options);
    }
}
